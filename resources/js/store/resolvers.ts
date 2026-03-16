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
import { __ } from '@wordpress/i18n';
import { UplinkError, ErrorCode } from '@/errors';
import type { Feature, ProductCatalog, License } from '@/types/api';
import type { Thunk } from './types';
import { forwardResolver, forwardResolverWithoutArgs } from '@/lib/forward-resolver';

/**
 * Fetches all features from the REST API and stores them.
 * Triggered automatically when getFeatures is first called.
 */
export const getFeatures =
	(): Thunk =>
	async ({ dispatch }) => {
		try {
			const features = await apiFetch<Feature[]>({
				path: '/stellarwp/uplink/v1/features',
			});
			dispatch.receiveFeatures(features);
		} catch (err) {
			throw await UplinkError.wrap(
				err,
				ErrorCode.FeaturesFetchFailed,
				__('Liquid Web Software failed to load your features.', '%TEXTDOMAIN%')
			);
		}
	};

export const getFeaturesByGroup = forwardResolverWithoutArgs('getFeatures');
export const getFeature = forwardResolverWithoutArgs('getFeatures');
export const isFeatureEnabled = forwardResolverWithoutArgs('getFeatures');

// ---------------------------------------------------------------------------
// Catalog
// ---------------------------------------------------------------------------

/**
 * Fetches all product catalogs from the REST API and stores them.
 * Triggered automatically when getCatalog is first called.
 */
export const getCatalog =
	(): Thunk =>
	async ({ dispatch }) => {
		try {
			const catalogs = await apiFetch<ProductCatalog[]>({
				path: '/stellarwp/uplink/v1/catalog',
			});
			dispatch.receiveCatalog(catalogs);
		} catch (err) {
			throw await UplinkError.wrap(
				err,
				ErrorCode.CatalogFetchFailed,
				__('Liquid Web Software failed to load the product catalog.', '%TEXTDOMAIN%')
			);
		}
	};

export const getProductCatalog = forwardResolverWithoutArgs('getCatalog');
export const getProductTiers   = forwardResolverWithoutArgs('getCatalog');
export const getCatalogTier    = forwardResolverWithoutArgs('getCatalog');

// ---------------------------------------------------------------------------
// License
// ---------------------------------------------------------------------------

/**
 * Fetches the stored license from the REST API.
 * Triggered automatically when getLicenseKey is first called.
 */
export const getLicenseKey =
	(): Thunk =>
	async ({ dispatch }) => {
		try {
			const result = await apiFetch<License>({
				path: '/stellarwp/uplink/v1/license',
			});
			dispatch.receiveLicense(result);
		} catch (err) {
			throw await UplinkError.wrap(
				err,
				ErrorCode.LicenseFetchFailed,
				__('Liquid Web Software failed to load your license.', '%TEXTDOMAIN%')
			);
		}
	};

export const hasLicense         = forwardResolver( 'getLicenseKey' );
export const getLicenseProducts = forwardResolverWithoutArgs( 'getLicenseKey' );
