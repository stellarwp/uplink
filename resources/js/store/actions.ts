/**
 * Action creators for the stellarwp/uplink @wordpress/data store.
 *
 * Plain action creators return objects handled by the reducer.
 * Thunk action creators return async functions handled by the
 * @wordpress/data thunk middleware.
 *
 * @package StellarWP\Uplink
 */
import apiFetch from '@wordpress/api-fetch';
import { UplinkError } from '@/errors';
import type { Feature } from '@/types/api';
import type { Action, UplinkThunk } from './types';

// ---------------------------------------------------------------------------
// Plain action creators (used by resolvers)
// ---------------------------------------------------------------------------

export function receiveFeatures( features: Feature[] ): Action {
	return { type: 'RECEIVE_FEATURES', features };
}

// ---------------------------------------------------------------------------
// Thunk action creators
// ---------------------------------------------------------------------------

export const enableFeature =
	( slug: string ): UplinkThunk =>
	async ( { dispatch } ) => {
		dispatch( { type: 'PATCH_FEATURE_START', slug, enabled: true } );
		try {
			const feature = await apiFetch< Feature >( {
				path: `/stellarwp/uplink/v1/features/${ slug }/enable`,
				method: 'POST',
			} );
			dispatch( { type: 'PATCH_FEATURE_FINISHED', feature } );
		} catch ( err ) {
			const error = UplinkError.from( err );
			dispatch( { type: 'PATCH_FEATURE_FAILED', slug, enabled: false, error } );
		}
	};

export const disableFeature =
	( slug: string ): UplinkThunk =>
	async ( { dispatch } ) => {
		dispatch( { type: 'PATCH_FEATURE_START', slug, enabled: false } );
		try {
			const feature = await apiFetch< Feature >( {
				path: `/stellarwp/uplink/v1/features/${ slug }/disable`,
				method: 'POST',
			} );
			dispatch( { type: 'PATCH_FEATURE_FINISHED', feature } );
		} catch ( err ) {
			const error = UplinkError.from( err );
			dispatch( { type: 'PATCH_FEATURE_FAILED', slug, enabled: true, error } );
		}
	};
