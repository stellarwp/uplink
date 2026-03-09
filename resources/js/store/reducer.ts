/**
 * Reducer for the stellarwp/uplink @wordpress/data store.
 *
 * @package StellarWP\Uplink
 */
import { combineReducers } from '@wordpress/data';
import type { Action, CatalogState, FeaturesState, LicenseState } from './types';

export const reducer = combineReducers({ features, license, catalog });

// ---------------------------------------------------------------------------
// Catalog
// ---------------------------------------------------------------------------

const CATALOG_DEFAULT: CatalogState = {
	byProductSlug: {},
};

function catalog(
	state: CatalogState = CATALOG_DEFAULT,
	action: Action
): CatalogState {
	switch (action.type) {
		case 'RECEIVE_CATALOG': {
			return {
				...state,
				byProductSlug: Object.fromEntries(
					action.catalogs.map((c) => [c.product_slug, c])
				),
			};
		}

		default:
			return state;
	}
}

// ---------------------------------------------------------------------------
// Features
// ---------------------------------------------------------------------------

const FEATURES_DEFAULT: FeaturesState = {
	bySlug: {},
	toggling: {},
	errorBySlug: {},
};

function features(
	state: FeaturesState = FEATURES_DEFAULT,
	action: Action
): FeaturesState {
	switch (action.type) {
		case 'RECEIVE_FEATURES': {
			return {
				...state,
				bySlug: Object.fromEntries(
					action.features.map((f) => [f.slug, f])
				),
			};
		}

		case 'TOGGLE_FEATURE_START': {
			const { [action.slug]: _, ...remainingErrors } = state.errorBySlug;
			return {
				...state,
				toggling: { ...state.toggling, [action.slug]: true },
				errorBySlug: remainingErrors,
			};
		}

		case 'TOGGLE_FEATURE_FINISHED': {
			const { slug } = action.feature;
			const { [slug]: _, ...remainingToggling } = state.toggling;
			return {
				...state,
				bySlug: {
					...state.bySlug,
					[slug]: action.feature,
				},
				toggling: remainingToggling,
			};
		}

		case 'TOGGLE_FEATURE_FAILED': {
			const { [action.slug]: _, ...remainingToggling } = state.toggling;
			return {
				...state,
				toggling: remainingToggling,
				errorBySlug: {
					...state.errorBySlug,
					[action.slug]: action.error,
				},
			};
		}

		default:
			return state;
	}
}

// ---------------------------------------------------------------------------
// License
// ---------------------------------------------------------------------------

const LICENSE_DEFAULT: LicenseState = {
	key: null,
	isActivating: false,
	isDeleting: false,
	activateError: null,
	deleteError: null,
};

function license(
	state: LicenseState = LICENSE_DEFAULT,
	action: Action
): LicenseState {
	switch (action.type) {
		case 'RECEIVE_LICENSE': {
			return {
				...state,
				key: action.key,
			};
		}

		case 'ACTIVATE_LICENSE_START': {
			return {
				...state,
				isActivating: true,
				activateError: null,
			};
		}

		case 'DELETE_LICENSE_START': {
			return {
				...state,
				isDeleting: true,
				deleteError: null,
			};
		}

		case 'ACTIVATE_LICENSE_FINISHED': {
			return {
				...state,
				isActivating: false,
				key: action.key,
			};
		}

		case 'DELETE_LICENSE_FINISHED': {
			return {
				...state,
				isDeleting: false,
				key: null,
			};
		}

		case 'ACTIVATE_LICENSE_FAILED': {
			return {
				...state,
				isActivating: false,
				activateError: action.error,
			};
		}

		case 'DELETE_LICENSE_FAILED': {
			return {
				...state,
				isDeleting: false,
				deleteError: action.error,
			};
		}

		default:
			return state;
	}
}
