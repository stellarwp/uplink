/**
 * Resolvers for the stellarwp/uplink @wordpress/data store.
 *
 * Each resolver name matches a selector. @wordpress/data calls the resolver
 * automatically the first time the matching selector is invoked, then marks
 * it as resolved so subsequent calls hit the cache.
 *
 * If a resolver throws, @wordpress/data marks it as a failed resolution.
 * useSuspenseSelect re-throws the error for an error boundary to catch.
 *
 * @package StellarWP\Uplink
 */
import apiFetch from '@wordpress/api-fetch';
import forwardResolver from '@/utils/data/forward-resolver';
import type { Feature } from '@/types/api';
import type { UplinkThunk } from './types';

export const getFeatures =
	(): UplinkThunk =>
	async ( { dispatch } ) => {
		const features = await apiFetch< Feature[] >( {
			path: '/stellarwp/uplink/v1/features',
		} );
		dispatch( { type: 'RECEIVE_FEATURES', features } );
	};

export const getFeaturesByGroup = forwardResolver( 'getFeatures' );
