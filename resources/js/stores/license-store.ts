/**
 * Zustand store for license and feature state.
 *
 * Business logic (validation, state computation) lives in `services/license-api.ts`.
 * This store is responsible for holding in-memory state and delegating every
 * mutation to the service layer so the API swap requires no changes here.
 *
 * @package StellarWP\Uplink
 */
import { create } from 'zustand';
import { persist, createJSONStorage } from 'zustand/middleware';
import * as licenseApi from '@/services/license-api';
import { licenseStorage } from '@/stores/license-storage';
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
    persist(
        ( set, get ) => ( {
            activeLicenses: [],
            featureStates:  [],
            productEnabled: {},

            /**
             * Validate a license key via the API (or mock) and merge the
             * resulting license + initial feature states into the store.
             * Returns an error string on failure, null on success.
             */
            activateLicense: async ( key ) => {
                const { activeLicenses, featureStates, productEnabled } = get();
                const result = await licenseApi.activateLicense( key, {
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
             */
            removeLicense: async ( key ) => {
                const { activeLicenses, featureStates, productEnabled } = get();
                const result = await licenseApi.deactivateLicense( key, {
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
        {
            name:    'stellarwp-uplink-licenses',
            storage: createJSONStorage( () => licenseStorage ),
            partialize: ( state ): Pick<LicenseStoreState, 'activeLicenses' | 'featureStates' | 'productEnabled'> => ( {
                activeLicenses: state.activeLicenses,
                featureStates:  state.featureStates,
                productEnabled: state.productEnabled,
            } ),
        },
    ),
);
