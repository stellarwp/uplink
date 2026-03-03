/**
 * Action creators for the stellarwp/uplink @wordpress/data store.
 *
 * Extended per phase:
 *   Phase 2 — receiveFeatures, setFeatureEnabled
 *   Phase 3 — enableFeature, disableFeature
 *
 * @see .plans/wp-data-store-features.md
 * @package StellarWP\Uplink
 */

export type Action =
    | { type: 'SET_ERROR';   key: string; message: string }
    | { type: 'CLEAR_ERROR'; key: string };

export const actions = {
    setError:   ( key: string, message: string ) => ( { type: 'SET_ERROR'   as const, key, message } ),
    clearError: ( key: string )                  => ( { type: 'CLEAR_ERROR' as const, key } ),
};
