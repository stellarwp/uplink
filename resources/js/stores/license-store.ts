/**
 * Zustand store for license and feature state.
 *
 * @package StellarWP\Uplink
 */
import { create } from 'zustand';
import { persist, createJSONStorage } from 'zustand/middleware';
import { __ } from '@wordpress/i18n';
import { findLicense } from '@/data/licenses';
import { PRODUCTS } from '@/data/products';
import { licenseStorage } from '@/stores/license-storage';
import type { License, FeatureState, TierSlug } from '@/types/api';

// ---------------------------------------------------------------------------
// Tier ordering for comparisons
// ---------------------------------------------------------------------------

const TIER_ORDER: Record<TierSlug, number> = {
    starter: 0,
    pro: 1,
    agency: 2,
};

export function tierGte( a: TierSlug, b: TierSlug ): boolean {
    return TIER_ORDER[ a ] >= TIER_ORDER[ b ];
}

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
    activateLicense: ( key: string ) => Promise<string | null>;
    removeLicense: ( key: string ) => void;
    toggleFeature: ( featureId: number, productSlug: string, enabled: boolean ) => void;
    toggleProduct: ( productSlug: string, enabled: boolean ) => void;

    // Selectors
    getLicenseForProduct: ( productSlug: string ) => License | null;
    getTierForProduct: ( productSlug: string ) => TierSlug | null;
    isFeatureEnabled: ( featureId: number, productSlug: string ) => boolean;
    hasLegacyLicense: () => boolean;
}

// ---------------------------------------------------------------------------
// Store
// ---------------------------------------------------------------------------

export const useLicenseStore = create<LicenseStoreState>()(
    persist(
        ( set, get ) => ( {
    activeLicenses: [],
    featureStates: [],
    productEnabled: {},

    activateLicense: async ( key: string ): Promise<string | null> => {
        // Simulate network delay
        await new Promise<void>( ( resolve ) => setTimeout( resolve, 1200 ) );

        const license = findLicense( key );

        if ( ! license ) {
            return __( 'Invalid license key. Please check and try again.', '%TEXTDOMAIN%' );
        }

        const { activeLicenses } = get();

        // Prevent duplicate
        if ( activeLicenses.some( ( l ) => l.key === key ) ) {
            return __( 'This license key is already activated.', '%TEXTDOMAIN%' );
        }

        set( ( state ) => {
            // Initialize feature states for all products this license covers
            const newFeatureStates = [ ...state.featureStates ];
            const newProductEnabled = { ...state.productEnabled };

            for ( const productSlug of license.productSlugs ) {
                // Enable product if not already set
                if ( newProductEnabled[ productSlug ] === undefined ) {
                    newProductEnabled[ productSlug ] = true;
                }

                // Initialize enabled features for accessible tiers
                const product = PRODUCTS.find( ( p ) => p.slug === productSlug );
                if ( product ) {
                    for ( const feature of product.features ) {
                        const alreadyExists = newFeatureStates.some(
                            ( fs ) => fs.featureId === feature.id && fs.productSlug === productSlug
                        );
                        if ( ! alreadyExists && tierGte( license.tier, feature.requiredTier ) ) {
                            newFeatureStates.push( {
                                featureId: feature.id,
                                productSlug,
                                enabled: true,
                            } );
                        }
                    }
                }
            }

            return {
                activeLicenses: [ ...state.activeLicenses, license ],
                featureStates: newFeatureStates,
                productEnabled: newProductEnabled,
            };
        } );

        return null; // null = success
    },

    removeLicense: ( key: string ) => {
        set( ( state ) => {
            const license = state.activeLicenses.find( ( l ) => l.key === key );
            if ( ! license ) return state;

            const remainingLicenses = state.activeLicenses.filter( ( l ) => l.key !== key );

            // Re-compute which product slugs still have coverage from remaining licenses
            const coveredProductSlugs = new Set(
                remainingLicenses.flatMap( ( l ) => l.productSlugs )
            );

            // Remove feature states for products no longer covered
            const newFeatureStates = state.featureStates.filter( ( fs ) =>
                coveredProductSlugs.has( fs.productSlug )
            );

            // Disable products that lost all coverage
            const newProductEnabled = { ...state.productEnabled };
            for ( const slug of license.productSlugs ) {
                if ( ! coveredProductSlugs.has( slug ) ) {
                    newProductEnabled[ slug ] = false;
                }
            }

            return {
                activeLicenses: remainingLicenses,
                featureStates: newFeatureStates,
                productEnabled: newProductEnabled,
            };
        } );
    },

    toggleFeature: ( featureId, productSlug, enabled ) => {
        set( ( state ) => {
            const exists = state.featureStates.some(
                ( fs ) => fs.featureId === featureId && fs.productSlug === productSlug
            );

            if ( exists ) {
                return {
                    featureStates: state.featureStates.map( ( fs ) =>
                        fs.featureId === featureId && fs.productSlug === productSlug
                            ? { ...fs, enabled }
                            : fs
                    ),
                };
            }

            return {
                featureStates: [
                    ...state.featureStates,
                    { featureId, productSlug, enabled },
                ],
            };
        } );
    },

    toggleProduct: ( productSlug, enabled ) => {
        set( ( state ) => ( {
            productEnabled: { ...state.productEnabled, [ productSlug ]: enabled },
        } ) );
    },

    getLicenseForProduct: ( productSlug ) => {
        const { activeLicenses } = get();
        return (
            activeLicenses.find( ( l ) => l.productSlugs.includes( productSlug ) ) ?? null
        );
    },

    getTierForProduct: ( productSlug ) => {
        const license = get().getLicenseForProduct( productSlug );
        return license?.tier ?? null;
    },

    isFeatureEnabled: ( featureId, productSlug ) => {
        const { featureStates } = get();
        const state = featureStates.find(
            ( fs ) => fs.featureId === featureId && fs.productSlug === productSlug
        );
        return state?.enabled ?? false;
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
        }
    )
);
