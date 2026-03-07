/**
 * Resolvers for the stellarwp/uplink @wordpress/data store.
 *
 * Each resolver name matches a selector. @wordpress/data calls the resolver
 * automatically the first time the matching selector is invoked, then marks
 * it as resolved so subsequent calls hit the cache.
 *
 * @package StellarWP\Uplink
 */
import apiFetch from '@wordpress/api-fetch';
import { dispatch } from '@wordpress/data';
import { STORE_NAME } from './constants';
import type { Feature } from '@/types/api';

export const resolvers = {
    /**
     * Fetches all features from the REST API and stores them.
     * Triggered automatically when getFeatures / getFeaturesByGroup is first called.
     */
    getFeatures: async (): Promise<void> => {
        const features = await apiFetch<Feature[]>( {
            path: '/stellarwp/uplink/v1/features',
        } );
        dispatch( STORE_NAME ).receiveFeatures( features );
    },

    /**
     * Fetches the stored license key from the REST API.
     * Triggered automatically when getLicense is first called.
     */
    getLicense: async (): Promise<void> => {
        const result = await apiFetch<{ key: string | null }>( {
            path: '/stellarwp/uplink/v1/license',
        } );
        dispatch( STORE_NAME ).receiveLicense( result.key );
    },
};
