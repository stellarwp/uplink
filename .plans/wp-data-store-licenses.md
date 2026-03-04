# WordPress Data Store Migration — Part 2: Licenses

<!-- cspell:ignore SCON LWSW -->

Replaces the remaining Zustand infrastructure (`license-store.ts`,
`license-storage.ts`) with the `stellarwp/uplink` `@wordpress/data` store,
wires up the live License REST endpoint, adds product status persistence, and
removes all mock/localStorage code.

> **Depends on:** Part 1 (`.plans/wp-data-store-features.md`) being complete.
> License REST API (`License_Controller`) is already in the codebase.
>
> **Architectural patterns:** Follow the same conventions introduced in
> PR #128 (`update/SCON-207/suggestions`) — `combineReducers`, individual
> named exports, `UplinkThunk`, `UplinkError`, `START/FINISHED/FAILED`
> lifecycle actions, and `createSelector` for memoized selectors.

---

## Key files

| File | Role |
|------|------|
| `resources/js/stores/license-store.ts` | **Delete** in Phase 7 |
| `resources/js/stores/license-storage.ts` | **Delete** in Phase 7 |
| `resources/js/services/license-api.ts` | Mock code removed; `tierGte` kept if still needed |
| `resources/js/data/licenses.ts` | **Delete** in Phase 7 |
| `resources/js/types/api.ts` | `License` aligned to real API shape |
| `resources/js/store/types.ts` | `LicenseState` + new `Action` variants added |

---

## Phase 4 — License Management

### Actual REST API shape

The unified license endpoint lives at `/stellarwp/uplink/v1/license`
**(singular)**, not `/licenses`. There is exactly **one** unified key per
site — not a list.

| Method | Path | Request | Response |
|--------|------|---------|----------|
| `GET` | `/stellarwp/uplink/v1/license` | — | `{ key: string \| null }` |
| `POST` | `/stellarwp/uplink/v1/license` | `{ key: string }` (must start with `LWSW-`) | `{ key: string }` |
| `DELETE` | `/stellarwp/uplink/v1/license` | — | `{ deleted: true }` |

- Invalid key format **or key not recognized by the remote licensing API** → **422** with `code: "INVALID_KEY"`
  - The POST endpoint calls `License_Manager::validate_and_store()`, which first checks the `LWSW-` prefix format and then queries the remote licensing API (`Product_Repository::get()`) before storing the key. A well-formatted key that is simply not registered in the licensing system will also produce a 422.
- Storage failure → **500**

Feature availability (`feature.is_available`) is computed **server-side** by
the Features endpoint based on the stored key. The license endpoint does not
return tier or product coverage data; the frontend only needs to know whether
a key exists.

**Cache invalidation:** `License_Repository` fires the WordPress action
`stellarwp/uplink/unified_license_key_changed` whenever the key is stored or
deleted. Three listeners (in the Catalog, Features, and Licensing providers)
immediately delete their respective PHP transient caches. This means the
next call to `GET /features` after an activate or deactivate will always
return freshly-resolved data — the frontend `invalidateResolution('getFeatures', [])`
approach in the store thunks is safe and correct.

**Legacy per-product licenses** are discoverable via the PHP filter
`stellarwp/uplink/legacy_licenses`. There is no REST endpoint for them yet.
Surface them to the frontend via `wp_localize_script` (initial page data) until
a dedicated endpoint is added.

---

### Type changes in `types/api.ts`

Replace the current `License` interface (which modelled a multi-license list)
with the actual API response shape:

```ts
/**
 * Unified license key as returned by GET/POST /stellarwp/uplink/v1/license.
 * @since 3.0.0
 */
export interface License {
    /** The stored unified license key */
    key: string;
}
```

Keep `LicenseStatus`, `LicenseProduct`, `FeatureState` marked `@deprecated`
until the old Zustand organisms are deleted.

---

### Store additions

All additions follow the PR #128 conventions: individual named exports,
`combineReducers`, `UplinkThunk`, `UplinkError`, and `START/FINISHED/FAILED`
lifecycle actions.

#### `store/types.ts` — add `LicenseState` and new `Action` variants

```ts
import type UplinkError from '@/errors/uplink-error';

export interface LicenseState {
    key:             string | null;
    isActivating:    boolean;
    isDeactivating:  boolean;
    activateError:   UplinkError | null;
    deactivateError: UplinkError | null;
}

// Add to State:
export interface State {
    features: Features;
    license:  LicenseState;
}

// Add to the Action union:
| { type: 'RECEIVE_LICENSE' }
| { type: 'ACTIVATE_LICENSE_START' }
| { type: 'ACTIVATE_LICENSE_FINISHED'; key: string }
| { type: 'ACTIVATE_LICENSE_FAILED';   error: UplinkError }
| { type: 'DEACTIVATE_LICENSE_START' }
| { type: 'DEACTIVATE_LICENSE_FINISHED' }
| { type: 'DEACTIVATE_LICENSE_FAILED';  error: UplinkError }
```

#### `store/reducer.ts` — add `license` sub-reducer via `combineReducers`

```ts
const LICENSE_DEFAULT: LicenseState = {
    key:             null,
    isActivating:    false,
    isDeactivating:  false,
    activateError:   null,
    deactivateError: null,
};

function license( state = LICENSE_DEFAULT, action: Action ): LicenseState {
    switch ( action.type ) {
        case 'RECEIVE_LICENSE':
            return { ...state, key: action.key ?? null };
        case 'ACTIVATE_LICENSE_START':
            return { ...state, isActivating: true, activateError: null };
        case 'ACTIVATE_LICENSE_FINISHED':
            return { ...state, isActivating: false, key: action.key };
        case 'ACTIVATE_LICENSE_FAILED':
            return { ...state, isActivating: false, activateError: action.error };
        case 'DEACTIVATE_LICENSE_START':
            return { ...state, isDeactivating: true, deactivateError: null };
        case 'DEACTIVATE_LICENSE_FINISHED':
            return { ...state, isDeactivating: false, key: null };
        case 'DEACTIVATE_LICENSE_FAILED':
            return { ...state, isDeactivating: false, deactivateError: action.error };
        default:
            return state;
    }
}

// Update combineReducers call:
export default combineReducers( { features, license } );

// Update initializeDefaultState:
export function initializeDefaultState(): State {
    return {
        features: { bySlug: {}, isUpdating: {}, errorBySlug: {} },
        license:  LICENSE_DEFAULT,
    };
}
```

#### `store/actions.ts` — add plain and thunk action creators

```ts
// Plain (used by resolver):
export function receiveLicense( key: string | null ): Action {
    return { type: 'RECEIVE_LICENSE', key };
}

// Thunks:
export const activateLicense =
    ( key: string ): UplinkThunk =>
    async ( { dispatch, registry } ) => {
        dispatch( { type: 'ACTIVATE_LICENSE_START' } );
        try {
            const result = await apiFetch< License >( {
                path:   '/stellarwp/uplink/v1/license',
                method: 'POST',
                data:   { key },
            } );
            dispatch( { type: 'ACTIVATE_LICENSE_FINISHED', key: result.key } );
            // Invalidate features resolver so is_available reflects new license.
            registry.dispatch( STORE_NAME ).invalidateResolution( 'getFeatures', [] );
        } catch ( err ) {
            dispatch( { type: 'ACTIVATE_LICENSE_FAILED', error: UplinkError.from( err ) } );
        }
    };

export const deactivateLicense =
    (): UplinkThunk =>
    async ( { dispatch, registry } ) => {
        dispatch( { type: 'DEACTIVATE_LICENSE_START' } );
        try {
            await apiFetch( {
                path:   '/stellarwp/uplink/v1/license',
                method: 'DELETE',
            } );
            dispatch( { type: 'DEACTIVATE_LICENSE_FINISHED' } );
            // Invalidate features resolver so is_available reflects removed license.
            registry.dispatch( STORE_NAME ).invalidateResolution( 'getFeatures', [] );
        } catch ( err ) {
            dispatch( { type: 'DEACTIVATE_LICENSE_FAILED', error: UplinkError.from( err ) } );
        }
    };
```

#### `store/resolvers.ts` — add `getLicense` resolver

```ts
export const getLicense =
    (): UplinkThunk =>
    async ( { dispatch } ) => {
        const result = await apiFetch< { key: string | null } >( {
            path: '/stellarwp/uplink/v1/license',
        } );
        dispatch( { type: 'RECEIVE_LICENSE', key: result.key } );
    };
```

#### `store/selectors.ts` — add

```ts
// Direct access (stable references):
export function getLicenseKey( state: State ): string | null {
    return state.license.key;
}

export function hasLicense( state: State ): boolean {
    return state.license.key !== null;
}

export function isLicenseActivating( state: State ): boolean {
    return state.license.isActivating;
}

export function isLicenseDeactivating( state: State ): boolean {
    return state.license.isDeactivating;
}

export function getActivateLicenseError( state: State ): UplinkError | null {
    return state.license.activateError;
}

export function getDeactivateLicenseError( state: State ): UplinkError | null {
    return state.license.deactivateError;
}
```

---

### Component updates

| Component | Before | After |
|-----------|--------|-------|
| `LicenseKeyInput` | `useLicenseStore().activateLicense(key)` | `useDispatch(uplinkStore).activateLicense(key)` + `useSelect → getActivateLicenseError` for inline error + `isLicenseActivating` for loading state |
| `LicenseCard` | `useLicenseStore().removeLicense(key)` | `useDispatch(uplinkStore).deactivateLicense()` + `getDeactivateLicenseError` as toast |
| `LicenseList` | Zustand `activeLicenses` (array) | `useSelect → getLicenseKey()` — now a single key; render one `LicenseCard` or empty state |
| `LegacyLicenseBanner` | `useLicenseStore().hasLegacyLicense()` | Read `window.uplink.legacyLicenses` passed via `wp_localize_script`; no store selector needed yet |
| `ProductSection` | Zustand `getLicenseForProduct`, `getTierForProduct` | Remove — feature availability comes from `feature.is_available` in the Features store |
| `FeatureRow` | Zustand `getTierForProduct` | Remove — use `feature.is_available` from the Features store |

> **Note on `LicenseList`:** The current mock data showed multiple license
> cards, but the API stores a single unified key. Update the component to
> render one card (or an empty/add-license prompt) rather than a list.
>
> **Note on legacy licenses:** Until a dedicated REST endpoint is added, pass
> the PHP filter results to the page via `wp_localize_script` (e.g. under
> `window.uplink.legacyLicenses`) and read them directly in
> `LegacyLicenseBanner` without a store selector.

---

### Error handling

Use `UplinkError.from( err )` for all caught errors (matching PR #128 pattern).

- `activateError` → displayed **inline** in `LicenseKeyInput` (not a toast)
- `deactivateError` → displayed as a **toast** in `LicenseCard`

---

### Verification

- Activate a valid key → `getLicenseKey()` updates, features re-fetch (`is_available` changes)
- Deactivate → `getLicenseKey()` returns `null`, features re-fetch
- Invalid key (wrong format, or valid format but not recognized by the licensing API) → 422 → inline error in `LicenseKeyInput`, no toast
- API error on deactivate → toast via `getDeactivateLicenseError`
- `bun run typecheck` passes

---

## Phase 5 — Product Status

**Depends on:** Product enable/disable REST endpoint landing.

**Planned endpoint:**
- `PUT /stellarwp/uplink/v1/products/{slug}/status` — `{ enabled: boolean }`

### Store additions

Follow PR #128 patterns throughout (individual exports, `combineReducers`,
`UplinkThunk`, `UplinkError`, `START/FINISHED/FAILED`).

#### `store/types.ts` — add `ProductStatusState` and new `Action` variants

```ts
export interface ProductStatusState {
    enabledBySlug: Record<string, boolean>;
    isUpdating:    Record<string, boolean>;
    errorBySlug:   Record<string, UplinkError>;
}

// Add to State:
export interface State {
    features:      Features;
    license:       LicenseState;
    productStatus: ProductStatusState;
}

// Add to Action union:
| { type: 'SET_PRODUCT_STATUS_START';    slug: string; enabled: boolean }
| { type: 'SET_PRODUCT_STATUS_FINISHED'; slug: string; enabled: boolean }
| { type: 'SET_PRODUCT_STATUS_FAILED';   slug: string; enabled: boolean; error: UplinkError }
```

#### `store/reducer.ts` — add `productStatus` sub-reducer

```ts
const PRODUCT_STATUS_DEFAULT: ProductStatusState = {
    enabledBySlug: {},
    isUpdating:    {},
    errorBySlug:   {},
};

function productStatus(
    state = PRODUCT_STATUS_DEFAULT,
    action: Action
): ProductStatusState {
    switch ( action.type ) {
        case 'SET_PRODUCT_STATUS_START': {
            const { [ action.slug ]: _, ...restErrors } = state.errorBySlug;
            return {
                ...state,
                enabledBySlug: { ...state.enabledBySlug, [ action.slug ]: action.enabled },
                isUpdating:    { ...state.isUpdating,    [ action.slug ]: true },
                errorBySlug:   restErrors,
            };
        }
        case 'SET_PRODUCT_STATUS_FINISHED':
            return {
                ...state,
                enabledBySlug: { ...state.enabledBySlug, [ action.slug ]: action.enabled },
                isUpdating:    { ...state.isUpdating,    [ action.slug ]: false },
            };
        case 'SET_PRODUCT_STATUS_FAILED':
            return {
                ...state,
                enabledBySlug: { ...state.enabledBySlug, [ action.slug ]: action.enabled },
                isUpdating:    { ...state.isUpdating,    [ action.slug ]: false },
                errorBySlug:   { ...state.errorBySlug,   [ action.slug ]: action.error },
            };
        default:
            return state;
    }
}

// Update combineReducers:
export default combineReducers( { features, license, productStatus } );

// Update initializeDefaultState:
export function initializeDefaultState(): State {
    return {
        features:      { bySlug: {}, isUpdating: {}, errorBySlug: {} },
        license:       LICENSE_DEFAULT,
        productStatus: PRODUCT_STATUS_DEFAULT,
    };
}
```

#### `store/actions.ts` — add thunk actions

```ts
export const enableProduct =
    ( slug: string ): UplinkThunk =>
    async ( { dispatch, select } ) => {
        const previous = select.isProductEnabled( slug );
        dispatch( { type: 'SET_PRODUCT_STATUS_START', slug, enabled: true } );
        try {
            await apiFetch( {
                path:   `/stellarwp/uplink/v1/products/${ encodeURIComponent( slug ) }/status`,
                method: 'PUT',
                data:   { enabled: true },
            } );
            dispatch( { type: 'SET_PRODUCT_STATUS_FINISHED', slug, enabled: true } );
        } catch ( err ) {
            dispatch( {
                type:    'SET_PRODUCT_STATUS_FAILED',
                slug,
                enabled: previous,
                error:   UplinkError.from( err ),
            } );
        }
    };

export const disableProduct =
    ( slug: string ): UplinkThunk =>
    async ( { dispatch, select } ) => {
        const previous = select.isProductEnabled( slug );
        dispatch( { type: 'SET_PRODUCT_STATUS_START', slug, enabled: false } );
        try {
            await apiFetch( {
                path:   `/stellarwp/uplink/v1/products/${ encodeURIComponent( slug ) }/status`,
                method: 'PUT',
                data:   { enabled: false },
            } );
            dispatch( { type: 'SET_PRODUCT_STATUS_FINISHED', slug, enabled: false } );
        } catch ( err ) {
            dispatch( {
                type:    'SET_PRODUCT_STATUS_FAILED',
                slug,
                enabled: previous,
                error:   UplinkError.from( err ),
            } );
        }
    };
```

#### `store/selectors.ts` — add

```ts
export function isProductEnabled( state: State, slug: string ): boolean {
    return state.productStatus.enabledBySlug[ slug ] ?? false;
}

export function isProductStatusUpdating( state: State, slug: string ): boolean {
    return state.productStatus.isUpdating[ slug ] ?? false;
}

export function getProductStatusError( state: State, slug: string ): UplinkError | null {
    return state.productStatus.errorBySlug[ slug ] ?? null;
}
```

### Component updates

| Component | Before | After |
|-----------|--------|-------|
| `ProductSection` | Zustand `toggleProduct()`, `productEnabled[slug]` | `useDispatch(uplinkStore).enableProduct()` / `disableProduct()` + `useSelect → isProductEnabled()` + `isProductStatusUpdating` for disabled state |
| `FeatureRow` | Zustand `productEnabled[slug]` | `useSelect → isProductEnabled( product.slug )` |

### Verification

- Enable/disable a product → optimistic toggle, server confirms
- API error → rollback, error toast via `getProductStatusError`
- Pending state while in-flight → button disabled
- `bun run typecheck` passes

---

## Phase 6 — Zip Install / Update _(awaiting API branch)_

**Depends on:** `base/SCON-115/implement-zip-strategy` and
`base/SCON-141/plugin-updates` merging.

Zip features (`type === 'zip'`) need additional UI:
- Install/download action
- Update-available indicator (from `Update_Client` in SCON-141)
- Progress/spinner state while downloading

Store additions and component design TBD once the PHP API shape is finalized.

---

## Phase 7 — Cleanup

Run after Phases 4–5 are complete and verified.

### Files to delete

```
resources/js/stores/license-store.ts
resources/js/stores/license-storage.ts
resources/js/data/licenses.ts
resources/js/services/license-api.ts   (if fully replaced; keep tierGte if still needed)
```

### Dependencies to remove

```bash
bun remove zustand
```

### Other cleanup

- Remove all `STELLARWP_UPLINK_FEATURES_USE_FIXTURE_DATA` fixture paths if
  the real API is stable in production
- Remove `@see .plans/wp-data-store-features.md` / `@see .plans/wp-data-store-licenses.md`
  JSDoc comments from files that no longer reference mock code
- Remove deprecated types (`LicenseStatus`, `LicenseProduct`, `FeatureState`,
  `LegacyFeature`) from `types/api.ts` once all consumers are migrated

---

## End-to-end verification

1. `bun run typecheck` — zero TS errors
2. `bun run lint:js` — zero linting errors
3. `bun run build` — clean production build, no `zustand` in bundle
4. WordPress admin → Feature Manager page loads without JS console errors
5. All REST calls in Network tab — nonce header present on every request
6. Manual test: activate license → `getLicenseKey()` updates, features re-fetch (availability changes)
7. Manual test: deactivate license → key cleared, features re-fetch
8. Manual test: invalid license key → inline error in `LicenseKeyInput`, no toast
9. Manual test: API error on deactivate → error toast in `LicenseCard`
10. Manual test: enable/disable product → optimistic toggle, features list shows/hides
11. Manual test: API error on product toggle → rollback, error toast
