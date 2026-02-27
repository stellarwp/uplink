# REST API + React Query Migration

Removes the mock localStorage persistence layer and Zustand in favour of
TanStack Query (server state) and React Context + `useState` (UI state).

---

## Dependencies

```bash
bun add @tanstack/react-query @wordpress/api-fetch
bun remove zustand
```

---

## New files to create

```
resources/js/
  hooks/
    use-license-state.ts          # useQuery  → GET    /wp-json/uplink/v1/state
    use-activate-license.ts       # useMutation → POST   /wp-json/uplink/v1/licenses
    use-deactivate-license.ts     # useMutation → DELETE /wp-json/uplink/v1/licenses/{key}
    use-update-product-status.ts  # useMutation → PUT    /wp-json/uplink/v1/products/{slug}/status  (optimistic)
    use-update-feature-status.ts  # useMutation → PUT    /wp-json/uplink/v1/features/{id}/{slug}/status (optimistic)
  context/
    toast-context.tsx             # React Context + useState replacing toast-store.ts
```

### Hook signatures

```ts
// hooks/use-license-state.ts
import { useQuery } from '@tanstack/react-query';
import * as licenseApi from '@/services/license-api';

export const LICENSE_STATE_KEY = [ 'uplink', 'state' ] as const;

export function useLicenseState() {
    return useQuery({
        queryKey: LICENSE_STATE_KEY,
        queryFn:  () => licenseApi.fetchState(),
    });
}
```

```ts
// hooks/use-activate-license.ts
import { useMutation, useQueryClient } from '@tanstack/react-query';
import * as licenseApi from '@/services/license-api';
import { LICENSE_STATE_KEY } from './use-license-state';

export function useActivateLicense() {
    const queryClient = useQueryClient();
    return useMutation( {
        mutationFn: ( key: string ) => licenseApi.activateLicense( key ),
        onSuccess:  () => queryClient.invalidateQueries( { queryKey: LICENSE_STATE_KEY } ),
    } );
}
```

```ts
// hooks/use-deactivate-license.ts
import { useMutation, useQueryClient } from '@tanstack/react-query';
import * as licenseApi from '@/services/license-api';
import { LICENSE_STATE_KEY } from './use-license-state';

export function useDeactivateLicense() {
    const queryClient = useQueryClient();
    return useMutation( {
        mutationFn: ( key: string ) => licenseApi.deactivateLicense( key ),
        onSuccess:  () => queryClient.invalidateQueries( { queryKey: LICENSE_STATE_KEY } ),
    } );
}
```

```ts
// hooks/use-update-product-status.ts  — optimistic update
import { useMutation, useQueryClient } from '@tanstack/react-query';
import * as licenseApi from '@/services/license-api';
import { LICENSE_STATE_KEY } from './use-license-state';
import type { LicenseState } from '@/services/license-api';

export function useUpdateProductStatus() {
    const queryClient = useQueryClient();
    return useMutation( {
        mutationFn: ( { slug, enabled }: { slug: string; enabled: boolean } ) =>
            licenseApi.updateProductStatus( slug, enabled ),
        onMutate: async ( { slug, enabled } ) => {
            await queryClient.cancelQueries( { queryKey: LICENSE_STATE_KEY } );
            const previous = queryClient.getQueryData<LicenseState>( LICENSE_STATE_KEY );
            queryClient.setQueryData<LicenseState>( LICENSE_STATE_KEY, ( old ) =>
                old ? { ...old, productEnabled: { ...old.productEnabled, [ slug ]: enabled } } : old
            );
            return { previous };
        },
        onError: ( _err, _vars, context ) => {
            queryClient.setQueryData( LICENSE_STATE_KEY, context?.previous );
        },
        onSettled: () => queryClient.invalidateQueries( { queryKey: LICENSE_STATE_KEY } ),
    } );
}
```

```ts
// hooks/use-update-feature-status.ts  — optimistic update
import { useMutation, useQueryClient } from '@tanstack/react-query';
import * as licenseApi from '@/services/license-api';
import { LICENSE_STATE_KEY } from './use-license-state';
import type { LicenseState } from '@/services/license-api';

export function useUpdateFeatureStatus() {
    const queryClient = useQueryClient();
    return useMutation( {
        mutationFn: ( { featureId, productSlug, enabled }: { featureId: number; productSlug: string; enabled: boolean } ) =>
            licenseApi.updateFeatureStatus( featureId, productSlug, enabled ),
        onMutate: async ( { featureId, productSlug, enabled } ) => {
            await queryClient.cancelQueries( { queryKey: LICENSE_STATE_KEY } );
            const previous = queryClient.getQueryData<LicenseState>( LICENSE_STATE_KEY );
            queryClient.setQueryData<LicenseState>( LICENSE_STATE_KEY, ( old ) => {
                if ( ! old ) return old;
                const exists = old.featureStates.some(
                    ( fs ) => fs.featureId === featureId && fs.productSlug === productSlug,
                );
                return {
                    ...old,
                    featureStates: exists
                        ? old.featureStates.map( ( fs ) =>
                            fs.featureId === featureId && fs.productSlug === productSlug
                                ? { ...fs, enabled }
                                : fs,
                        )
                        : [ ...old.featureStates, { featureId, productSlug, enabled } ],
                };
            } );
            return { previous };
        },
        onError: ( _err, _vars, context ) => {
            queryClient.setQueryData( LICENSE_STATE_KEY, context?.previous );
        },
        onSettled: () => queryClient.invalidateQueries( { queryKey: LICENSE_STATE_KEY } ),
    } );
}
```

### Toast context (replaces toast-store.ts)

```tsx
// context/toast-context.tsx
import { createContext, useCallback, useContext, useState, type ReactNode } from 'react';

export type ToastVariant = 'default' | 'success' | 'error';

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

    const addToast = useCallback( ( message: string, variant: ToastVariant = 'default' ) => {
        const id = crypto.randomUUID();
        setToasts( ( prev ) => [ ...prev, { id, message, variant } ] );
        setTimeout( () => removeToast( id ), 3500 );
    }, [ removeToast ] );

    return (
        <ToastContext.Provider value={ { toasts, addToast, removeToast } }>
            { children }
        </ToastContext.Provider>
    );
}

export const useToast = () => useContext( ToastContext );
```

---

## Files to update

### `App.tsx`
Wrap with `QueryClientProvider` and `ToastProvider`:

```tsx
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { ToastProvider } from '@/context/toast-context';

const queryClient = new QueryClient();

export const App = () => (
    <QueryClientProvider client={ queryClient }>
        <ToastProvider>
            <AppShell />
            <Toaster />
        </ToastProvider>
    </QueryClientProvider>
);
```

### `services/license-api.ts`
- Uncomment all `// API:` lines, delete all `// Mock:` blocks.
- Remove `current: LicenseState` parameter from `activateLicense` and `deactivateLicense`.
- Delete `STORAGE_KEY`, `emptyState`, `readMockStorage`.

### `stores/license-store.ts`
Delete the entire file. Nothing replaces it directly — state is derived
from `useLicenseState().data` in each component, mutations go through
the dedicated mutation hooks.

`tierGte` is still exported from `services/license-api.ts`; components
import it from there directly instead of via the store re-export.

### `stores/license-storage.ts`
Delete the entire file.

### `stores/toast-store.ts`
Delete the entire file. Replaced by `context/toast-context.tsx`.

---

## Component-by-component changes

### `components/organisms/LicenseList.tsx`
| Before | After |
|--------|-------|
| `useLicenseStore(s => s.activeLicenses)` | `useLicenseState().data?.activeLicenses ?? []` |

### `components/organisms/ProductSection.tsx`
| Before | After |
|--------|-------|
| `getLicenseForProduct(slug)` | `data?.activeLicenses.find(l => l.productSlugs.includes(slug)) ?? null` |
| `getTierForProduct(slug)` | derived from license above |
| `productEnabled[slug]` | `data?.productEnabled[slug] ?? false` |
| `toggleProduct(slug, next)` | `updateProductStatus.mutate({ slug, enabled: next })` |
| `useToastStore().addToast` | `useToast().addToast` |

### `components/molecules/LegacyLicenseBanner.tsx`
| Before | After |
|--------|-------|
| `useLicenseStore(s => s.hasLegacyLicense())` | `useLicenseState().data?.activeLicenses.some(l => l.type === 'legacy') ?? false` |

### `components/molecules/FeatureRow.tsx`
| Before | After |
|--------|-------|
| `getTierForProduct(slug)` | `data?.activeLicenses.find(...)?.tier ?? null` |
| `isFeatureEnabled(id, slug)` | `data?.featureStates.find(...)?.enabled ?? false` |
| `productEnabled[slug]` | `data?.productEnabled[slug] ?? false` |
| `toggleFeature(id, slug, enabled)` | `updateFeatureStatus.mutate({ featureId: id, productSlug: slug, enabled })` |
| `tierGte` | import directly from `@/services/license-api` |
| `useToastStore().addToast` | `useToast().addToast` |

### `components/molecules/LicenseCard.tsx`
| Before | After |
|--------|-------|
| `useLicenseStore().removeLicense(key)` | `useDeactivateLicense().mutate(key)` |

### `components/molecules/LicenseKeyInput.tsx`
| Before | After |
|--------|-------|
| `useLicenseStore().activateLicense(key)` | `useActivateLicense().mutateAsync(key)` |
| `useToastStore().addToast` | `useToast().addToast` |

### `components/ui/toast.tsx`
| Before | After |
|--------|-------|
| `useToastStore()` | `useToast()` from `@/context/toast-context` |

---

## Files to delete after migration

```
resources/js/stores/license-store.ts
resources/js/stores/license-storage.ts
resources/js/stores/toast-store.ts
```
