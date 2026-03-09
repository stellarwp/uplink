/**
 * Action creators for the stellarwp/uplink @wordpress/data store.
 *
 * @package StellarWP\Uplink
 */

import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { UplinkError, ErrorCode } from '@/errors';
import type { Feature, License, ProductCatalog } from '@/types/api';
import type { Action, Thunk } from './types';

// ---------------------------------------------------------------------------
// Plain action creators (synchronous)
// ---------------------------------------------------------------------------

export const receiveFeatures = (features: Feature[]): Action => ({
	type: 'RECEIVE_FEATURES',
	features,
});

export const receiveLicense = (key: string | null): Action => ({
	type: 'RECEIVE_LICENSE',
	key,
});

export const receiveCatalog = (catalogs: ProductCatalog[]): Action => ({
	type: 'RECEIVE_CATALOG',
	catalogs,
});

// ---------------------------------------------------------------------------
// Thunk action creators (async)
// ---------------------------------------------------------------------------

/**
 * Enable a feature via the REST API.
 *
 * @since 3.0.0
 */
export const enableFeature =
	(slug: string): Thunk<UplinkError | null> =>
	async ({ dispatch }) => {
		dispatch({ type: 'TOGGLE_FEATURE_START', slug });
		try {
			const feature = await apiFetch<Feature>({
				path: `/stellarwp/uplink/v1/features/${slug}/enable`,
				method: 'POST',
			});
			dispatch({ type: 'TOGGLE_FEATURE_FINISHED', feature });
			return null;
		} catch (err) {
			const error = UplinkError.wrap(
				err,
				ErrorCode.FeatureEnableFailed,
				__('Liquid Web Software failed to enable your feature.', '%TEXTDOMAIN%')
			);
			dispatch({ type: 'TOGGLE_FEATURE_FAILED', slug, error });
			return error;
		}
	};

/**
 * Disable a feature via the REST API.
 *
 * @since 3.0.0
 */
export const disableFeature =
	(slug: string): Thunk<UplinkError | null> =>
	async ({ dispatch }) => {
		dispatch({ type: 'TOGGLE_FEATURE_START', slug });
		try {
			const feature = await apiFetch<Feature>({
				path: `/stellarwp/uplink/v1/features/${slug}/disable`,
				method: 'POST',
			});
			dispatch({ type: 'TOGGLE_FEATURE_FINISHED', feature });
			return null;
		} catch (err) {
			const error = UplinkError.wrap(
				err,
				ErrorCode.FeatureDisableFailed,
				__('Liquid Web Software failed to disable your feature.', '%TEXTDOMAIN%')
			);
			dispatch({ type: 'TOGGLE_FEATURE_FAILED', slug, error });
			return error;
		}
	};

/**
 * Activate a license key via the REST API, then invalidate the
 * features resolver so the UI refreshes with the new entitlements.
 *
 * @since 3.0.0
 */
export const activateLicense =
	(key: string): Thunk<UplinkError | null> =>
	async ({ dispatch, select }) => {
		if (!select.canModifyLicense()) {
			return new UplinkError(
				ErrorCode.LicenseActionInProgress,
				__('Liquid Web Software failed to activate your license, another action is in progress.', '%TEXTDOMAIN%')
			);
		}
		dispatch({ type: 'ACTIVATE_LICENSE_START' });
		try {
			const result = await apiFetch<License>({
				path: '/stellarwp/uplink/v1/license',
				method: 'POST',
				data: { key },
			});
			dispatch({
				type: 'ACTIVATE_LICENSE_FINISHED',
				key: result.key,
			});
			dispatch.invalidateResolution('getFeatures', []);
			return null;
		} catch (err) {
			const error = UplinkError.wrap(
				err,
				ErrorCode.LicenseActivateFailed,
				__('Liquid Web Software failed to activate your license.', '%TEXTDOMAIN%')
			);
			dispatch({ type: 'ACTIVATE_LICENSE_FAILED', error });
			return error;
		}
	};

/**
 * Delete the stored license key via the REST API, then invalidate the
 * features resolver so the UI refreshes.
 *
 * @since 3.0.0
 */
export const deleteLicense =
	(): Thunk<UplinkError | null> =>
	async ({ dispatch, select }) => {
		if (!select.canModifyLicense()) {
			return new UplinkError(
				ErrorCode.LicenseActionInProgress,
				__('Liquid Web Software failed to delete your license, another action is in progress.', '%TEXTDOMAIN%')
			);
		}
		dispatch({ type: 'DELETE_LICENSE_START' });
		try {
			await apiFetch<void>({
				path: '/stellarwp/uplink/v1/license',
				method: 'DELETE',
			});
			dispatch({ type: 'DELETE_LICENSE_FINISHED' });
			dispatch.invalidateResolution('getFeatures', []);
			return null;
		} catch (err) {
			const error = UplinkError.wrap(
				err,
				ErrorCode.LicenseDeleteFailed,
				__('Liquid Web Software failed to remove your license.', '%TEXTDOMAIN%')
			);
			dispatch({ type: 'DELETE_LICENSE_FAILED', error });
			return error;
		}
	};
