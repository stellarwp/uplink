/**
 * License API service — single abstraction between the store and the data source.
 *
 * Every function mirrors one REST API endpoint. While the real API is not yet
 * available each function falls back to mock data + localStorage. The lines
 * marked "API:" are the real implementation; the lines marked "Mock:" are the
 * temporary stand-in.
 *
 * Migration checklist (do these steps when the REST API is ready):
 *  1. `bun add @wordpress/api-fetch`
 *  2. Uncomment every "API:" line and delete every "Mock:" block.
 *  3. Remove the `persist` middleware from `stores/license-store.ts`.
 *  4. Add an `initialize()` action to `license-store.ts` that calls `fetchState()`.
 *  5. Call `useLicenseStore.getState().initialize()` from `App.tsx` on mount.
 *  6. Delete `stores/license-storage.ts` (no longer needed).
 *
 * @package StellarWP\Uplink
 */
import { __ } from '@wordpress/i18n';
import { findLicense } from '@/data/licenses';
import { PRODUCTS } from '@/data/products';
import type { License, FeatureState, TierSlug } from '@/types/api';

// ---------------------------------------------------------------------------
// Tier helpers
// ---------------------------------------------------------------------------

const TIER_ORDER: Record<TierSlug, number> = { starter: 0, pro: 1, agency: 2 };

/** Returns true when tier `a` is equal to or higher than tier `b`. */
export function tierGte( a: TierSlug, b: TierSlug ): boolean {
    return TIER_ORDER[ a ] >= TIER_ORDER[ b ];
}

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

/** The subset of store state that the API reads and writes. */
export interface LicenseState {
    activeLicenses: License[];
    featureStates:  FeatureState[];
    productEnabled: Record<string, boolean>;
}

export interface ActivateSuccess {
    license:        License;
    featureStates:  FeatureState[];
    productEnabled: Record<string, boolean>;
}

// ---------------------------------------------------------------------------
// Mock persistence helpers — deleted when the REST API is active.
// ---------------------------------------------------------------------------

/** Key used by the Zustand persist middleware; read here for initialisation. */
const STORAGE_KEY = 'stellarwp-uplink-licenses';

function emptyState(): LicenseState {
    return { activeLicenses: [], featureStates: [], productEnabled: {} };
}

function readMockStorage(): LicenseState {
    try {
        const raw = localStorage.getItem( STORAGE_KEY );
        if ( ! raw ) return emptyState();
        // Zustand persist wraps state as { state: {...}, version: N }
        const parsed = JSON.parse( raw ) as { state?: LicenseState };
        return parsed.state ?? emptyState();
    } catch {
        return emptyState();
    }
}

// ---------------------------------------------------------------------------
// API functions
// ---------------------------------------------------------------------------

/**
 * GET /wp-json/uplink/v1/state
 *
 * Returns the full license + feature state persisted for this site.
 * Used to hydrate the store on mount once the `persist` middleware is removed.
 *
 * @todo Step 4 of migration checklist — add `initialize()` to the store that
 *       calls this, then invoke it from App.tsx.
 */
export async function fetchState(): Promise<LicenseState> {
    // API: return apiFetch<LicenseState>({ path: '/uplink/v1/state' });

    // Mock: the Zustand persist middleware already hydrates the store from
    // localStorage on mount, so this path is only reached once persist is
    // removed. Read the same key persist writes so data is never lost.
    return readMockStorage();
}

/**
 * POST /wp-json/uplink/v1/licenses
 *
 * Validates a license key and returns the license object together with the
 * initial feature / product-enabled state that the server computed — matching
 * the shape the store needs to merge into its local state.
 *
 * Returns `{ error }` on failure so callers never need to catch.
 */
export async function activateLicense(
    key: string,
    current: LicenseState,
): Promise<ActivateSuccess | { error: string }> {
    // API: return apiFetch<ActivateSuccess>({
    // API:     path:   '/uplink/v1/licenses',
    // API:     method: 'POST',
    // API:     data:   { key },
    // API: });

    // Mock: simulate network round-trip + validate against fixture data.
    await new Promise<void>( ( resolve ) => setTimeout( resolve, 1200 ) );

    const license = findLicense( key );
    if ( ! license ) {
        return { error: __( 'Invalid license key. Please check and try again.', '%TEXTDOMAIN%' ) };
    }
    if ( current.activeLicenses.some( ( l ) => l.key === key ) ) {
        return { error: __( 'This license key is already activated.', '%TEXTDOMAIN%' ) };
    }

    // Mirror what the server would compute: seed feature / product-enabled
    // state for every product this license covers.
    const featureStates  = [ ...current.featureStates ];
    const productEnabled = { ...current.productEnabled };

    for ( const productSlug of license.productSlugs ) {
        if ( productEnabled[ productSlug ] === undefined ) {
            productEnabled[ productSlug ] = true;
        }
        const product = PRODUCTS.find( ( p ) => p.slug === productSlug );
        if ( product ) {
            for ( const feature of product.features ) {
                const exists = featureStates.some(
                    ( fs ) => fs.featureId === feature.id && fs.productSlug === productSlug,
                );
                if ( ! exists && tierGte( license.tier, feature.requiredTier ) ) {
                    featureStates.push( { featureId: feature.id, productSlug, enabled: true } );
                }
            }
        }
    }

    return { license, featureStates, productEnabled };
}

/**
 * DELETE /wp-json/uplink/v1/licenses/{key}
 *
 * Removes a license. Returns the cleaned-up state so the store can replace
 * its slice in one shot — features and product toggles for products that lost
 * all coverage are pruned.
 *
 * Returns `{ error }` on failure so callers never need to catch.
 */
export async function deactivateLicense(
    key: string,
    current: LicenseState,
): Promise<LicenseState | { error: string }> {
    // API: await apiFetch({ path: `/uplink/v1/licenses/${ encodeURIComponent( key ) }`, method: 'DELETE' });
    // API: return fetchState(); // re-fetch authoritative state from the server

    // Mock: derive the post-removal state locally.
    const license = current.activeLicenses.find( ( l ) => l.key === key );
    if ( ! license ) {
        return { error: __( 'License not found.', '%TEXTDOMAIN%' ) };
    }

    const activeLicenses = current.activeLicenses.filter( ( l ) => l.key !== key );
    const coveredSlugs   = new Set( activeLicenses.flatMap( ( l ) => l.productSlugs ) );
    const featureStates  = current.featureStates.filter( ( fs ) => coveredSlugs.has( fs.productSlug ) );
    const productEnabled = { ...current.productEnabled };

    for ( const slug of license.productSlugs ) {
        if ( ! coveredSlugs.has( slug ) ) {
            productEnabled[ slug ] = false;
        }
    }

    return { activeLicenses, featureStates, productEnabled };
}

/**
 * PUT /wp-json/uplink/v1/products/{slug}/status
 *
 * Persists a product's enabled / disabled state server-side.
 * In mock mode the Zustand persist middleware writes to localStorage
 * automatically whenever the store changes — nothing extra needed here.
 */
export async function updateProductStatus(
    _slug: string,
    _enabled: boolean,
): Promise<void> {
    // API: await apiFetch({
    // API:     path:   `/uplink/v1/products/${ encodeURIComponent( _slug ) }/status`,
    // API:     method: 'PUT',
    // API:     data:   { enabled: _enabled },
    // API: });

    // Mock: persisted automatically by Zustand persist middleware.
}

/**
 * PUT /wp-json/uplink/v1/features/{featureId}/{productSlug}/status
 *
 * Persists a feature's enabled / disabled state server-side.
 * In mock mode the Zustand persist middleware writes to localStorage
 * automatically whenever the store changes — nothing extra needed here.
 */
export async function updateFeatureStatus(
    _featureId: number,
    _productSlug: string,
    _enabled: boolean,
): Promise<void> {
    // API: await apiFetch({
    // API:     path:   `/uplink/v1/features/${ _featureId }/${ encodeURIComponent( _productSlug ) }/status`,
    // API:     method: 'PUT',
    // API:     data:   { enabled: _enabled },
    // API: });

    // Mock: persisted automatically by Zustand persist middleware.
}
