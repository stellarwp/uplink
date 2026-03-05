/**
 * Action creators for the stellarwp/uplink @wordpress/data store.
 *
 * Plain action creators (returning objects) are handled by the reducer.
 * Thunk action creators (returning async functions) are handled by the
 * @wordpress/data thunk middleware — automatically included in v10+.
 *
 * @package StellarWP\Uplink
 */
import apiFetch from '@wordpress/api-fetch';
import { dispatch as wpDispatch } from '@wordpress/data';
import { STORE_NAME } from './constants';
import type { Feature, License } from '@/types/api';

// ---------------------------------------------------------------------------
// Discriminated union for plain (reducer-handled) actions only.
// Thunk actions are not part of this union.
// ---------------------------------------------------------------------------

export type Action =
    | { type: 'SET_ERROR';                key: string; message: string }
    | { type: 'CLEAR_ERROR';              key: string }
    | { type: 'RECEIVE_FEATURES';         features: Feature[] }
    | { type: 'PATCH_FEATURE';            slug: string; enabled: boolean }
    | { type: 'RECEIVE_LICENSE';          key: string | null }
    | { type: 'ACTIVATE_LICENSE_START' }
    | { type: 'ACTIVATE_LICENSE_FINISHED'; key: string }
    | { type: 'ACTIVATE_LICENSE_FAILED';   error: string }
    | { type: 'DELETE_LICENSE_START' }
    | { type: 'DELETE_LICENSE_FINISHED' }
    | { type: 'DELETE_LICENSE_FAILED';     error: string };

// ---------------------------------------------------------------------------
// Thunk dispatch interfaces — the object passed to thunk action bodies.
// ---------------------------------------------------------------------------

type ThunkDispatch = {
    setError:          ( key: string, message: string ) => void;
    clearError:        ( key: string ) => void;
    setFeatureEnabled: ( slug: string, enabled: boolean ) => void;
};

type LicenseThunkDispatch = ( action: Action ) => void;

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

    receiveLicense: ( key: string | null ) =>
        ( { type: 'RECEIVE_LICENSE' as const, key } ),

    // -- Thunk action creators (async, with optimistic update + rollback) --

    /**
     * Enable a feature: optimistic update → POST to REST API → rollback on error.
     * @since 3.0.0
     */
    enableFeature: ( slug: string ) =>
        async ( { dispatch }: { dispatch: ThunkDispatch } ): Promise<string | null> => {
            dispatch.clearError( `feature:${ slug }` );
            dispatch.setFeatureEnabled( slug, true );
            try {
                await apiFetch<void>( {
                    path:   `/stellarwp/uplink/v1/features/${ slug }/enable`,
                    method: 'POST',
                } );
                return null;
            } catch ( err ) {
                dispatch.setFeatureEnabled( slug, false );
                const message = ( err as Error ).message;
                dispatch.setError( `feature:${ slug }`, message );
                return message;
            }
        },

    /**
     * Disable a feature: optimistic update → POST to REST API → rollback on error.
     * @since 3.0.0
     */
    disableFeature: ( slug: string ) =>
        async ( { dispatch }: { dispatch: ThunkDispatch } ): Promise<string | null> => {
            dispatch.clearError( `feature:${ slug }` );
            dispatch.setFeatureEnabled( slug, false );
            try {
                await apiFetch<void>( {
                    path:   `/stellarwp/uplink/v1/features/${ slug }/disable`,
                    method: 'POST',
                } );
                return null;
            } catch ( err ) {
                dispatch.setFeatureEnabled( slug, true );
                const message = ( err as Error ).message;
                dispatch.setError( `feature:${ slug }`, message );
                return message;
            }
        },

    /**
     * Activate a license key: POST to REST API → store key → invalidate features resolver.
     * @since 3.0.0
     */
    activateLicense: ( key: string ) =>
        async ( { dispatch }: { dispatch: LicenseThunkDispatch } ): Promise<void> => {
            dispatch( { type: 'ACTIVATE_LICENSE_START' } );
            try {
                const result = await apiFetch<License>( {
                    path:   '/stellarwp/uplink/v1/license',
                    method: 'POST',
                    data:   { key },
                } );
                dispatch( { type: 'ACTIVATE_LICENSE_FINISHED', key: result.key } );
                wpDispatch( STORE_NAME ).invalidateResolution( 'getFeatures', [] );
            } catch ( err ) {
                dispatch( { type: 'ACTIVATE_LICENSE_FAILED', error: ( err as Error ).message } );
            }
        },

    /**
     * Delete the stored license key: DELETE to REST API → clear key → invalidate features resolver.
     * @since 3.0.0
     */
    deleteLicense: () =>
        async ( { dispatch }: { dispatch: LicenseThunkDispatch } ): Promise<void> => {
            dispatch( { type: 'DELETE_LICENSE_START' } );
            try {
                await apiFetch<void>( {
                    path:   '/stellarwp/uplink/v1/license',
                    method: 'DELETE',
                } );
                dispatch( { type: 'DELETE_LICENSE_FINISHED' } );
                wpDispatch( STORE_NAME ).invalidateResolution( 'getFeatures', [] );
            } catch ( err ) {
                dispatch( { type: 'DELETE_LICENSE_FAILED', error: ( err as Error ).message } );
            }
        },
};
