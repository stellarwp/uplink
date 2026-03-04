# WordPress Data Store Migration — Part 1: Features

Replaces the `QueryClientProvider` / Zustand infrastructure with a
`@wordpress/data` custom store (`stellarwp/uplink`) and wires up the live
Features REST endpoints (`stellarwp/uplink/v1/features`).

> **Part 2 (Licenses):** see `.plans/wp-data-store-licenses.md` — depends on
> the License REST API landing in a separate branch.
>
> **Previous filename:** `rest-api-react-query-migration.md` — referenced in
> `@see` JSDoc comments throughout the codebase; update those as files are
> migrated.

<!-- cspell:ignore SCON -->
Three branches (`base/SCON-115`, `base/SCON-141`, `feature/SCON-206`) already
implement the Features REST endpoints used here.

**Error surfaces:** toasts (transient, existing pattern), inline field errors,
and React error boundaries (unexpected render crashes).

---

## Key files

| File | Role |
|------|------|
| `resources/js/index.tsx` | App entry — registers WP store, remove `QueryClientProvider` |
| `resources/js/App.tsx` | Root component — add `ToastProvider` + error boundary |
| `resources/js/stores/toast-store.ts` | **Delete** in Phase 3 |
| `resources/js/types/api.ts` | Extended in Phase 2 |
| `src/Uplink/Admin/Feature_Manager_Page.php` | Add `wp-data`, `wp-api-fetch` to script deps |
| `webpack.config.js` | No changes — `@wordpress/scripts` handles externals |

> `stores/license-store.ts`, `stores/license-storage.ts`, and `zustand` itself
> remain untouched until Part 2.

---

## Phase 1 — Foundation: WordPress Data Store + Error Boundary

**Goal:** Replace `QueryClientProvider` with a bare `@wordpress/data` store
skeleton and add a React error boundary.

### Dependencies

```bash
bun add @wordpress/data @wordpress/api-fetch
bun remove @tanstack/react-query
```

> `zustand` stays installed; `license-store.ts` and `toast-store.ts` remain
> active in parallel during the transition.

### New files

**`resources/js/store/constants.ts`**
```ts
export const STORE_NAME = 'stellarwp/uplink' as const;
```

**`resources/js/store/reducer.ts`**
```ts
import type { Action } from './actions';

export interface State {
    features:       Record<string, Feature>;   // keyed by slug — filled in Phase 2
    errors:         Record<string, string>;    // action-scoped error messages
}

const DEFAULT_STATE: State = {
    features: {},
    errors:   {},
};

export function reducer( state = DEFAULT_STATE, action: Action ): State {
    switch ( action.type ) {
        case 'SET_ERROR':
            return { ...state, errors: { ...state.errors, [ action.key ]: action.message } };
        case 'CLEAR_ERROR': {
            const { [ action.key ]: _, ...rest } = state.errors;
            return { ...state, errors: rest };
        }
        default:
            return state;
    }
}
```

**`resources/js/store/actions.ts`** — bare scaffold, extended per phase
```ts
export type Action =
    | { type: 'SET_ERROR'; key: string; message: string }
    | { type: 'CLEAR_ERROR'; key: string };

export const actions = {
    setError:   ( key: string, message: string ) => ( { type: 'SET_ERROR'   as const, key, message } ),
    clearError: ( key: string )                  => ( { type: 'CLEAR_ERROR' as const, key } ),
};
```

**`resources/js/store/selectors.ts`** — bare scaffold
```ts
import type { State } from './reducer';

export const selectors = {
    getError: ( state: State, key: string ) => state.errors[ key ] ?? null,
};
```

**`resources/js/store/resolvers.ts`** — empty, extended in Phase 2
```ts
export const resolvers = {};
```

**`resources/js/store/index.ts`**
```ts
import { createReduxStore, register } from '@wordpress/data';
import { reducer }   from './reducer';
import { actions }   from './actions';
import { selectors } from './selectors';
import { resolvers } from './resolvers';
import { STORE_NAME } from './constants';

export const store = createReduxStore( STORE_NAME, { reducer, actions, selectors, resolvers } );
export const registerUplinkStore = () => register( store );
export { STORE_NAME };
```

**`resources/js/components/ErrorBoundary.tsx`**
```tsx
import { Component, type ReactNode } from 'react';
import { __ } from '@wordpress/i18n';

interface Props  { children: ReactNode; fallback?: ReactNode; }
interface BState { hasError: boolean; }

export class ErrorBoundary extends Component<Props, BState> {
    state = { hasError: false };
    static getDerivedStateFromError() { return { hasError: true }; }
    render() {
        return this.state.hasError
            ? ( this.props.fallback ?? <p>{ __( 'Something went wrong.', '%TEXTDOMAIN%' ) }</p> )
            : this.props.children;
    }
}
```

### Files to update

**`resources/js/index.tsx`**
- Remove `QueryClient`, `QueryClientProvider`
- Call `registerUplinkStore()` before `createRoot()`

**`resources/js/App.tsx`**
- Wrap `<AppShell />` in `<ErrorBoundary>`

**`src/Uplink/Admin/Feature_Manager_Page.php`** (line ~105)
- Add `'wp-data'` and `'wp-api-fetch'` to `$asset_data['dependencies']`

### Verification
- `bun run typecheck` passes
- App still mounts and renders (Zustand store still active in parallel)
- Temporarily throw in `AppShell`, confirm error boundary fallback renders

---

## Phase 2 — Features Read: List via REST

**Goal:** Wire up `GET /stellarwp/uplink/v1/features` as a resolver; update
Feature types and `ProductSection`/`FeatureRow` to read from the store.

### Type changes in `resources/js/types/api.ts`

Add the real API shape alongside existing types:
```ts
// Real API feature (from REST endpoint)
export type FeatureType = 'zip' | 'built_in';

export interface Feature {
    slug:              string;
    name:              string;
    description:       string;
    group:             string;    // maps to product slug
    tier:              TierSlug;  // minimum tier required to access
    type:              FeatureType;
    is_available:      boolean;
    documentation_url: string;
    is_enabled:        boolean;
}
```

Keep `ProductFeature` (old type) until all consumers are migrated.

### Store additions

**`store/reducer.ts`** — add `features` slice + action handlers:
```ts
case 'RECEIVE_FEATURES':
    return {
        ...state,
        features: Object.fromEntries( action.features.map( ( f: Feature ) => [ f.slug, f ] ) ),
    };
case 'PATCH_FEATURE':
    return {
        ...state,
        features: {
            ...state.features,
            [ action.slug ]: { ...state.features[ action.slug ], is_enabled: action.is_enabled },
        },
    };
```

**`store/actions.ts`** — add:
```ts
receiveFeatures:   ( features: Feature[] ) => ( { type: 'RECEIVE_FEATURES' as const, features } ),
setFeatureEnabled: ( slug: string, is_enabled: boolean ) =>
    ( { type: 'PATCH_FEATURE' as const, slug, is_enabled } ),
```

**`store/resolvers.ts`**
```ts
import apiFetch from '@wordpress/api-fetch';
import { dispatch } from '@wordpress/data';
import { STORE_NAME } from './constants';
import type { Feature } from '@/types/api';

export const resolvers = {
    getFeatures: async () => {
        const features: Feature[] = await apiFetch( { path: '/stellarwp/uplink/v1/features' } );
        dispatch( STORE_NAME ).receiveFeatures( features );
    },
};
```

**`store/selectors.ts`** — add:
```ts
getFeatures:        ( state: State ) => Object.values( state.features ),
getFeaturesByGroup: ( state: State, group: string ) =>
    Object.values( state.features ).filter( ( f ) => f.group === group ),
getFeature:         ( state: State, slug: string ) => state.features[ slug ] ?? null,
```

### Component updates

**`components/organisms/ProductSection.tsx`**
- Replace `product.features` (from `data/products.ts`) with:
  ```ts
  const features = useSelect(
      ( select ) => select( STORE_NAME ).getFeaturesByGroup( product.slug ),
      [ product.slug ],
  );
  ```
- Check resolver status via `hasFinishedResolution( 'getFeatures', [] )` for
  loading skeleton
- Pass `feature: Feature` to `<FeatureRow>`

**`components/molecules/FeatureRow.tsx`**
- Props: accept `Feature` (slug-based) instead of `ProductFeature` (id-based)
- `feature.tier` replaces `feature.requiredTier`; upgrade URL still from
  `product.tiers`
- `feature.is_enabled` drives the toggle display state
- Toggle dispatches `dispatch( STORE_NAME ).setFeatureEnabled( slug, checked )`
  (no API call yet — wired up in Phase 3)
- License/product state (tier check, `productEnabled`) still read from Zustand
  until Part 2

### Verification
- Features load from the real endpoint (or `STELLARWP_UPLINK_FEATURES_USE_FIXTURE_DATA` fixture)
- Loading skeleton renders while resolver is pending; no flash of empty content
- `bun run typecheck` passes

---

## Phase 3 — Feature Enable/Disable + Error Handling

**Goal:** Wire up `POST /stellarwp/uplink/v1/features/{slug}/enable` and
`/disable`; replace `toast-store.ts` with React Context; add inline/toast
error handling; add per-tab error boundaries.

### Toast context (replaces `stores/toast-store.ts`)

Create **`resources/js/context/toast-context.tsx`**:
```tsx
import { createContext, useCallback, useContext, useState, type ReactNode } from 'react';

export type ToastVariant = 'default' | 'success' | 'error' | 'warning';

export interface Toast {
    id:      string;
    message: string;
    variant: ToastVariant;
}

interface ToastContextValue {
    toasts:      Toast[];
    addToast:    ( message: string, variant?: ToastVariant ) => void;
    removeToast: ( id: string ) => void;
}

const ToastContext = createContext<ToastContextValue>( {
    toasts:      [],
    addToast:    () => {},
    removeToast: () => {},
} );

export function ToastProvider( { children }: { children: ReactNode } ) {
    const [ toasts, setToasts ] = useState<Toast[]>( [] );

    const removeToast = useCallback( ( id: string ) => {
        setToasts( ( prev ) => prev.filter( ( t ) => t.id !== id ) );
    }, [] );

    const addToast = useCallback(
        ( message: string, variant: ToastVariant = 'default' ) => {
            const id = crypto.randomUUID();
            setToasts( ( prev ) => [ ...prev, { id, message, variant } ] );
            setTimeout( () => removeToast( id ), 3500 );
        },
        [ removeToast ],
    );

    return (
        <ToastContext.Provider value={ { toasts, addToast, removeToast } }>
            { children }
        </ToastContext.Provider>
    );
}

export const useToast = () => useContext( ToastContext );
```

**`resources/js/App.tsx`** — wrap in `<ToastProvider>`

**`resources/js/components/ui/toast.tsx`** — swap `useToastStore()` →
`useToast()` from `@/context/toast-context`

**Delete** `resources/js/stores/toast-store.ts`

### Store additions

**`store/actions.ts`** — add thunk actions:
```ts
enableFeature: ( slug: string ) => async ( { dispatch }: { dispatch: typeof actions } ) => {
    dispatch.clearError( `feature:${ slug }` );
    dispatch.setFeatureEnabled( slug, true );
    try {
        await apiFetch( {
            path:   `/stellarwp/uplink/v1/features/${ slug }/enable`,
            method: 'POST',
        } );
    } catch ( err ) {
        dispatch.setFeatureEnabled( slug, false );
        dispatch.setError( `feature:${ slug }`, ( err as Error ).message );
    }
},

disableFeature: ( slug: string ) => async ( { dispatch }: { dispatch: typeof actions } ) => {
    dispatch.clearError( `feature:${ slug }` );
    dispatch.setFeatureEnabled( slug, false );
    try {
        await apiFetch( {
            path:   `/stellarwp/uplink/v1/features/${ slug }/disable`,
            method: 'POST',
        } );
    } catch ( err ) {
        dispatch.setFeatureEnabled( slug, true );
        dispatch.setError( `feature:${ slug }`, ( err as Error ).message );
    }
},
```

**`store/selectors.ts`** — add:
```ts
isFeatureEnabled: ( state: State, slug: string ) => state.features[ slug ]?.is_enabled ?? false,
getFeatureError:  ( state: State, slug: string ) => state.errors[ `feature:${ slug }` ] ?? null,
```

### Component updates

**`components/molecules/FeatureRow.tsx`**
- Replace `dispatch( STORE_NAME ).setFeatureEnabled()` (Phase 2 stub) with
  `dispatch( STORE_NAME ).enableFeature()` / `disableFeature()`
- Read `getFeatureError( slug )` from store; surface via
  `useToast().addToast( error, 'error' )`
- Pending/disabled state while mutation is in-flight

**`components/templates/AppShell.tsx`**
- Wrap each tab panel in its own `<ErrorBoundary fallback={...}>` so a crash
  in one tab doesn't break the other

### Delete
- `resources/js/stores/toast-store.ts`

### Verification
- Enable/disable a feature → optimistic toggle, success toast
- Simulate API error (wrong nonce / 403) → rollback toggle, error toast
- Crash one tab → error boundary fallback; other tab still works
- `bun run typecheck` and `bun run lint:js` pass

---

## End-to-end verification

1. `bun run typecheck` — zero TS errors
2. `bun run lint:js` — zero linting errors
3. `bun run build` — clean production build, asset manifest updated
4. WordPress admin → Feature Manager page loads without JS console errors
5. REST calls visible in browser Network tab hitting `stellarwp/uplink/v1/features`
6. Nonce header (`X-WP-Nonce`) present on all `@wordpress/api-fetch` requests
7. Manual test: enable/disable a feature → optimistic toggle, toast, server persisted
8. Manual test: wrong nonce / 403 → rollback, error toast
9. Manual test: render crash in one tab → boundary fallback, other tab intact
