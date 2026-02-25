/**
 * Zustand store for license and feature state.
 *
 * Business logic (validation, state computation) lives in `services/license-api.ts`.
 * This store is responsible for holding in-memory state and delegating every
 * mutation to the service layer so the API swap requires no changes here —
 * except for the persist middleware (see TODOs below).
 *
 * @todo (step 3): Remove the `persist` and `createJSONStorage` imports.
 * @todo (step 3): Remove the `licenseStorage` import and delete `stores/license-storage.ts`.
 * @todo (step 3): Replace the `create<LicenseStoreState>()( persist( ... ) )` wrapper
 *                 with a plain `create<LicenseStoreState>()( ( set, get ) => ({ ... }) )`.
 * @todo (step 4): Add an `initialize` action to the `LicenseStoreState` interface:
 *                   initialize: () => Promise<void>;
 *                 And implement it inside the store:
 *                   initialize: async () => {
 *                       const state = await licenseApi.fetchState();
 *                       set( state );
 *                   },
 *
 * @package StellarWP\Uplink
 */
import { create } from 'zustand';
import { persist, createJSONStorage } from 'zustand/middleware'; // @TODO (step 3): Remove this import.
import * as licenseApi from '@/services/license-api';
import { licenseStorage } from '@/stores/license-storage'; // @TODO (step 3): Remove this import.
import type { License, FeatureState, TierSlug } from '@/types/api';

// Re-export so existing callers (e.g. FeatureRow) don't need to change their imports.
export { tierGte } from '@/services/license-api';

// ---------------------------------------------------------------------------
// State shape
// ---------------------------------------------------------------------------

interface LicenseStoreState {
    /** All currently activated license keys */
    activeLicenses: License[];
    /** Per-feature enabled/disabled toggles */
    featureStates: FeatureState[];
    /** Whether each product's main toggle is on */
    productEnabled: Record<string, boolean>;

    // @TODO (step 4): Add initialize action:
    // initialize: () => Promise<void>;

    // Actions
    activateLicense:  ( key: string ) => Promise<string | null>;
    removeLicense:    ( key: string ) => Promise<void>;
    toggleFeature:    ( featureId: number, productSlug: string, enabled: boolean ) => Promise<void>;
    toggleProduct:    ( productSlug: string, enabled: boolean ) => Promise<void>;

    // Selectors
    getLicenseForProduct: ( productSlug: string ) => License | null;
    getTierForProduct:    ( productSlug: string ) => TierSlug | null;
    isFeatureEnabled:     ( featureId: number, productSlug: string ) => boolean;
    hasLegacyLicense:     () => boolean;
}

// ---------------------------------------------------------------------------
// Store
// ---------------------------------------------------------------------------

export const useLicenseStore = create<LicenseStoreState>()(
    // @TODO (step 3): Remove the `persist( ... , { name, storage, partialize } )` wrapper.
    //                 Keep only the inner callback: create<LicenseStoreState>()( ( set, get ) => ({ ... }) )
    persist(
        ( set, get ) => ( {
            activeLicenses: [],
            featureStates:  [],
            productEnabled: {},

            // @TODO (step 4): Add initialize action here:
            // initialize: async () => {
            //     const state = await licenseApi.fetchState();
            //     set( state );
            // },

            /**
             * Validate a license key via the API (or mock) and merge the
             * resulting license + initial feature states into the store.
             * Returns an error string on failure, null on success.
             *
             * @todo (step 7): Remove the `current` object passed to `licenseApi.activateLicense`
             *                 once the service no longer needs client-side state.
             *                 Change to: const result = await licenseApi.activateLicense( key );
             */
            activateLicense: async ( key ) => {
                const { activeLicenses, featureStates, productEnabled } = get(); // @TODO (step 7): Remove destructuring.
                const result = await licenseApi.activateLicense( key, { // @TODO (step 7): Remove second argument.
                    activeLicenses,
                    featureStates,
                    productEnabled,
                } );

                if ( 'error' in result ) return result.error;

                set( ( state ) => ( {
                    activeLicenses: [ ...state.activeLicenses, result.license ],
                    featureStates:  result.featureStates,
                    productEnabled: result.productEnabled,
                } ) );

                return null; // null = success
            },

            /**
             * Remove a license via the API (or mock) and replace the relevant
             * state slices with the cleaned-up version returned by the service.
             *
             * @todo (step 7): Remove the `current` object passed to `licenseApi.deactivateLicense`
             *                 once the service no longer needs client-side state.
             *                 Change to: const result = await licenseApi.deactivateLicense( key );
             */
            removeLicense: async ( key ) => {
                const { activeLicenses, featureStates, productEnabled } = get(); // @TODO (step 7): Remove destructuring.
                const result = await licenseApi.deactivateLicense( key, { // @TODO (step 7): Remove second argument.
                    activeLicenses,
                    featureStates,
                    productEnabled,
                } );

                if ( 'error' in result ) return;

                set( result );
            },

            /**
             * Toggle a feature. State is updated optimistically; the API call
             * (currently a no-op mock) persists the change server-side.
             */
            toggleFeature: async ( featureId, productSlug, enabled ) => {
                set( ( state ) => {
                    const exists = state.featureStates.some(
                        ( fs ) => fs.featureId === featureId && fs.productSlug === productSlug,
                    );
                    return {
                        featureStates: exists
                            ? state.featureStates.map( ( fs ) =>
                                fs.featureId === featureId && fs.productSlug === productSlug
                                    ? { ...fs, enabled }
                                    : fs,
                            )
                            : [ ...state.featureStates, { featureId, productSlug, enabled } ],
                    };
                } );

                await licenseApi.updateFeatureStatus( featureId, productSlug, enabled );
            },

            /**
             * Toggle a product. State is updated optimistically; the API call
             * (currently a no-op mock) persists the change server-side.
             */
            toggleProduct: async ( productSlug, enabled ) => {
                set( ( state ) => ( {
                    productEnabled: { ...state.productEnabled, [ productSlug ]: enabled },
                } ) );

                await licenseApi.updateProductStatus( productSlug, enabled );
            },

            // Selectors -------------------------------------------------------

            getLicenseForProduct: ( productSlug ) => {
                const { activeLicenses } = get();
                return activeLicenses.find( ( l ) => l.productSlugs.includes( productSlug ) ) ?? null;
            },

            getTierForProduct: ( productSlug ) => {
                return get().getLicenseForProduct( productSlug )?.tier ?? null;
            },

            isFeatureEnabled: ( featureId, productSlug ) => {
                const fs = get().featureStates.find(
                    ( s ) => s.featureId === featureId && s.productSlug === productSlug,
                );
                return fs?.enabled ?? false;
            },

            hasLegacyLicense: () => {
                return get().activeLicenses.some( ( l ) => l.type === 'legacy' );
            },
        } ),
        // @TODO (step 3): Delete from here to the closing `) );` below.
        {
            name:    'stellarwp-uplink-licenses',
            storage: createJSONStorage( () => licenseStorage ),
            partialize: ( state ): Pick<LicenseStoreState, 'activeLicenses' | 'featureStates' | 'productEnabled'> => ( {
                activeLicenses: state.activeLicenses,
                featureStates:  state.featureStates,
                productEnabled: state.productEnabled,
            } ),
        },
        // @TODO (step 3): End of block to delete.
    ),
);
