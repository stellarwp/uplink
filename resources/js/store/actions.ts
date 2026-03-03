/**
 * Action creators for the stellarwp/uplink @wordpress/data store.
 *
 * Thunk actions (enableFeature, disableFeature) added in the next commit.
 *
 * @see .plans/wp-data-store-features.md
 * @package StellarWP\Uplink
 */
import type { Feature } from '@/types/api';

export type Action =
    | { type: 'SET_ERROR';        key: string; message: string }
    | { type: 'CLEAR_ERROR';      key: string }
    | { type: 'RECEIVE_FEATURES'; features: Feature[] }
    | { type: 'PATCH_FEATURE';    slug: string; enabled: boolean };

export const actions = {
    setError:   ( key: string, message: string ) => ( { type: 'SET_ERROR'   as const, key, message } ),
    clearError: ( key: string )                  => ( { type: 'CLEAR_ERROR' as const, key } ),

    receiveFeatures: ( features: Feature[] ) =>
        ( { type: 'RECEIVE_FEATURES' as const, features } ),

    setFeatureEnabled: ( slug: string, enabled: boolean ) =>
        ( { type: 'PATCH_FEATURE' as const, slug, enabled } ),
};
