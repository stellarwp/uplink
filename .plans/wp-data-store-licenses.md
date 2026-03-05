# WordPress Data Store Migration — Part 2: Licenses

Replaces the remaining Zustand infrastructure (`license-store.ts`,
`license-storage.ts`) with the `stellarwp/uplink` `@wordpress/data` store,
wires up the live License REST endpoints, adds product status persistence, and
removes all mock/localStorage code.

> **Depends on:** Part 1 (`.plans/wp-data-store-features.md`) being complete,
> and the License REST API branch merging first.

---

## Key files

| File | Role |
|------|------|
| `resources/js/stores/license-store.ts` | **Delete** in Phase 6 |
| `resources/js/stores/license-storage.ts` | **Delete** in Phase 6 |
| `resources/js/services/license-api.ts` | Mock code removed; `tierGte` kept |
| `resources/js/data/licenses.ts` | **Delete** in Phase 6 |
| `resources/js/types/api.ts` | `License` aligned to real API shape |

---

## Phase 4 — License Management

**Depends on:** License REST endpoints landing in a separate branch.

**Planned endpoints:**
- `POST   /stellarwp/uplink/v1/licenses`         — activate
- `DELETE /stellarwp/uplink/v1/licenses/{key}`   — deactivate
- `GET    /stellarwp/uplink/v1/licenses`          — list active

### Type changes in `types/api.ts`

Align `License` to the real API response shape (inline update once the PHP
branch is merged and the response contract is known).

### Store additions

**`store/reducer.ts`** — add `licenses` slice:
```ts
// Add to State:
licenses: License[];

// Add to DEFAULT_STATE:
licenses: [],

// Add cases:
case 'RECEIVE_LICENSES':
    return { ...state, licenses: action.licenses };
case 'REMOVE_LICENSE':
    return { ...state, licenses: state.licenses.filter( ( l ) => l.key !== action.key ) };
```

**`store/actions.ts`** — add thunk actions:
```ts
activateLicense: ( key: string ) => async ( { dispatch } ) => {
    dispatch.clearError( 'license:activate' );
    try {
        const license: License = await apiFetch( {
            path:   '/stellarwp/uplink/v1/licenses',
            method: 'POST',
            data:   { key },
        } );
        dispatch.receiveLicenses( [ ...currentLicenses, license ] );
    } catch ( err ) {
        dispatch.setError( 'license:activate', ( err as Error ).message );
    }
},

deactivateLicense: ( key: string ) => async ( { dispatch, select } ) => {
    // Optimistic remove
    const previous = select.getLicenses();
    dispatch.removeLicense( key );
    try {
        await apiFetch( {
            path:   `/stellarwp/uplink/v1/licenses/${ encodeURIComponent( key ) }`,
            method: 'DELETE',
        } );
    } catch ( err ) {
        dispatch.receiveLicenses( previous );
        dispatch.setError( `license:${ key }`, ( err as Error ).message );
    }
},

receiveLicenses: ( licenses: License[] ) => ( { type: 'RECEIVE_LICENSES' as const, licenses } ),
removeLicense:   ( key: string )         => ( { type: 'REMOVE_LICENSE'   as const, key } ),
```

**`store/resolvers.ts`** — add:
```ts
getLicenses: async () => {
    const licenses: License[] = await apiFetch( { path: '/stellarwp/uplink/v1/licenses' } );
    dispatch( STORE_NAME ).receiveLicenses( licenses );
},
```

**`store/selectors.ts`** — add:
```ts
getLicenses:          ( state: State ) => state.licenses,
getLicenseForProduct: ( state: State, slug: string ) =>
    state.licenses.find( ( l ) => l.productSlugs.includes( slug ) ) ?? null,
getTierForProduct:    ( state: State, slug: string ) =>
    state.licenses.find( ( l ) => l.productSlugs.includes( slug ) )?.tier ?? null,
hasLegacyLicense:     ( state: State ) =>
    state.licenses.some( ( l ) => l.type === 'legacy' ),
getLicenseError:      ( state: State, key: string ) =>
    state.errors[ `license:${ key }` ] ?? null,
getActivateLicenseError: ( state: State ) =>
    state.errors[ 'license:activate' ] ?? null,
```

### Component updates

| Component | Before | After |
|-----------|--------|-------|
| `LicenseKeyInput` | `useLicenseStore().activateLicense(key)` | `useDispatch(STORE_NAME).activateLicense(key)` + read `getActivateLicenseError` for inline error |
| `LicenseCard` | `useLicenseStore().removeLicense(key)` | `useDispatch(STORE_NAME).deactivateLicense(key)` |
| `LicenseList` | Zustand `activeLicenses` | `useSelect` → `getLicenses()` |
| `LegacyLicenseBanner` | `useLicenseStore().hasLegacyLicense()` | `useSelect` → `hasLegacyLicense()` |
| `ProductSection` | Zustand `getLicenseForProduct`, `getTierForProduct` | `useSelect` → store selectors |
| `FeatureRow` | Zustand `getTierForProduct` | `useSelect` → `getTierForProduct()` |

### Verification
- Add a license → appears in `LicenseList`, tier badge updates in `ProductSection`
- Remove a license → optimistic remove, product reverts to unlicensed state on error
- Invalid key → inline error in `LicenseKeyInput`, no toast
- `bun run typecheck` passes

---

## Phase 5 — Product Status

**Depends on:** Product enable/disable REST endpoint landing.

**Planned endpoint:**
- `PUT /stellarwp/uplink/v1/products/{slug}/status` — `{ enabled: boolean }`

### Store additions

**`store/reducer.ts`** — add `productEnabled` slice:
```ts
// Add to State:
productEnabled: Record<string, boolean>;

// Add to DEFAULT_STATE:
productEnabled: {},

// Add case:
case 'SET_PRODUCT_ENABLED':
    return {
        ...state,
        productEnabled: { ...state.productEnabled, [ action.slug ]: action.enabled },
    };
```

**`store/actions.ts`** — add thunk actions:
```ts
enableProduct: ( slug: string ) => async ( { dispatch, select } ) => {
    const previous = select.isProductEnabled( slug );
    dispatch.setProductEnabled( slug, true );
    try {
        await apiFetch( {
            path:   `/stellarwp/uplink/v1/products/${ encodeURIComponent( slug ) }/status`,
            method: 'PUT',
            data:   { enabled: true },
        } );
    } catch ( err ) {
        dispatch.setProductEnabled( slug, previous );
        dispatch.setError( `product:${ slug }`, ( err as Error ).message );
    }
},

disableProduct: ( slug: string ) => async ( { dispatch, select } ) => {
    const previous = select.isProductEnabled( slug );
    dispatch.setProductEnabled( slug, false );
    try {
        await apiFetch( {
            path:   `/stellarwp/uplink/v1/products/${ encodeURIComponent( slug ) }/status`,
            method: 'PUT',
            data:   { enabled: false },
        } );
    } catch ( err ) {
        dispatch.setProductEnabled( slug, previous );
        dispatch.setError( `product:${ slug }`, ( err as Error ).message );
    }
},

setProductEnabled: ( slug: string, enabled: boolean ) =>
    ( { type: 'SET_PRODUCT_ENABLED' as const, slug, enabled } ),
```

**`store/selectors.ts`** — add:
```ts
isProductEnabled: ( state: State, slug: string ) => state.productEnabled[ slug ] ?? false,
```

### Component updates

| Component | Before | After |
|-----------|--------|-------|
| `ProductSection` | Zustand `toggleProduct()`, `productEnabled[slug]` | `useDispatch(STORE_NAME).enableProduct()` / `disableProduct()` + `useSelect` → `isProductEnabled()` |
| `FeatureRow` | Zustand `productEnabled[slug]` | `useSelect` → `isProductEnabled( product.slug )` |

### Verification
- Activate/deactivate a product → optimistic toggle, features list shows/hides
- API error → rollback toggle, error toast
- `bun run typecheck` passes

---

## Phase 6 — Plugin Install / Update _(awaiting API branch)_

<!-- cspell:ignore SCON -->
**Depends on:** `base/SCON-115/implement-plugin-strategy` and
`base/SCON-141/plugin-updates` merging.

Plugin features (`type === 'plugin'`) need additional UI:
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

---

## End-to-end verification

1. `bun run typecheck` — zero TS errors
2. `bun run lint:js` — zero linting errors
3. `bun run build` — clean production build, no `zustand` in bundle
4. WordPress admin → Feature Manager page loads without JS console errors
5. All REST calls in Network tab — nonce header present on every request
6. Manual test: activate license → tier badge, features unlock
7. Manual test: deactivate license → optimistic remove, rollback on error
8. Manual test: invalid license key → inline error in `LicenseKeyInput`
9. Manual test: activate/deactivate product → feature list shows/hides
10. Manual test: API error on product toggle → rollback, error toast
