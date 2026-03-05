/**
 * Selectors for the stellarwp/uplink @wordpress/data store.
 *
 * @package StellarWP\Uplink
 */
import type { State } from './reducer';
import type { Feature } from '@/types/api';

export const selectors = {
    // -------------------------------------------------------------------------
    // Errors
    // -------------------------------------------------------------------------

    getError: ( state: State, key: string ): string | null =>
        state.errors[ key ] ?? null,

    // -------------------------------------------------------------------------
    // Features
    // -------------------------------------------------------------------------

    getFeatures: ( state: State ): Feature[] =>
        Object.values( state.features ),

    getFeaturesByGroup: ( state: State, group: string ): Feature[] =>
        Object.values( state.features ).filter( ( f ) => f.group === group ),

    getFeature: ( state: State, slug: string ): Feature | null =>
        state.features[ slug ] ?? null,

    isFeatureEnabled: ( state: State, slug: string ): boolean =>
        state.features[ slug ]?.is_enabled ?? false,

    getFeatureError: ( state: State, slug: string ): string | null =>
        state.errors[ `feature:${ slug }` ] ?? null,

    // -------------------------------------------------------------------------
    // License
    // -------------------------------------------------------------------------

    /** Returns the stored unified license key, or null. Triggers getLicense resolver. */
    getLicense: ( state: State ): string | null =>
        state.license.key,

    hasLicense: ( state: State ): boolean =>
        state.license.key !== null,

    isLicenseActivating: ( state: State ): boolean =>
        state.license.isActivating,

    isLicenseDeleting: ( state: State ): boolean =>
        state.license.isDeleting,

    getActivateLicenseError: ( state: State ): string | null =>
        state.license.activateError,

    getDeleteLicenseError: ( state: State ): string | null =>
        state.license.deleteError,
};
