/**
 * Action creators for the stellarwp/uplink @wordpress/data store.
 *
 * Plain action creators (returning objects) are handled by the reducer.
 * Thunk action creators (returning async functions) are handled by the
 * @wordpress/data thunk middleware — automatically included in v10+.
 *
 * @see .plans/wp-data-store-features.md
 * @package StellarWP\Uplink
 */
import apiFetch from '@wordpress/api-fetch';
import type { Feature } from '@/types/api';

// ---------------------------------------------------------------------------
// Discriminated union for plain (reducer-handled) actions only.
// Thunk actions are not part of this union.
// ---------------------------------------------------------------------------

export type Action =
    | { type: 'SET_ERROR';        key: string; message: string }
    | { type: 'CLEAR_ERROR';      key: string }
    | { type: 'RECEIVE_FEATURES'; features: Feature[] }
    | { type: 'PATCH_FEATURE';    slug: string; enabled: boolean };

// ---------------------------------------------------------------------------
// Thunk dispatch interface — the object passed to thunk action bodies.
// ---------------------------------------------------------------------------

type ThunkDispatch = {
    setError:          ( key: string, message: string ) => void;
    clearError:        ( key: string ) => void;
    setFeatureEnabled: ( slug: string, enabled: boolean ) => void;
};

// ---------------------------------------------------------------------------
// Action creators
// ---------------------------------------------------------------------------

export const actions = {
    // -- Plain action creators (synchronous) --

    setError: ( key: string, message: string ) =>
        ( { type: 'SET_ERROR' as const, key, message } ),

    clearError: ( key: string ) =>
        ( { type: 'CLEAR_ERROR' as const, key } ),

    receiveFeatures: ( features: Feature[] ) =>
        ( { type: 'RECEIVE_FEATURES' as const, features } ),

    setFeatureEnabled: ( slug: string, enabled: boolean ) =>
        ( { type: 'PATCH_FEATURE' as const, slug, enabled } ),

    // -- Thunk action creators (async, with optimistic update + rollback) --

    /**
     * Enable a feature: optimistic update → POST to REST API → rollback on error.
     * @since 3.0.0
     */
    enableFeature: ( slug: string ) =>
        async ( { dispatch }: { dispatch: ThunkDispatch } ): Promise<void> => {
            dispatch.clearError( `feature:${ slug }` );
            dispatch.setFeatureEnabled( slug, true );
            try {
                await apiFetch<void>( {
                    path:   `/stellarwp/uplink/v1/features/${ slug }/enable`,
                    method: 'POST',
                } );
            } catch ( err ) {
                dispatch.setFeatureEnabled( slug, false );
                dispatch.setError( `feature:${ slug }`, ( err as Error ).message );
            }
        },

    /**
     * Disable a feature: optimistic update → POST to REST API → rollback on error.
     * @since 3.0.0
     */
    disableFeature: ( slug: string ) =>
        async ( { dispatch }: { dispatch: ThunkDispatch } ): Promise<void> => {
            dispatch.clearError( `feature:${ slug }` );
            dispatch.setFeatureEnabled( slug, false );
            try {
                await apiFetch<void>( {
                    path:   `/stellarwp/uplink/v1/features/${ slug }/disable`,
                    method: 'POST',
                } );
            } catch ( err ) {
                dispatch.setFeatureEnabled( slug, true );
                dispatch.setError( `feature:${ slug }`, ( err as Error ).message );
            }
        },
};
