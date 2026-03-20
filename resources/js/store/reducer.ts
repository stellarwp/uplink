/**
 * Reducer for the stellarwp/uplink @wordpress/data store.
 *
 * @package StellarWP\Uplink
 */
import { combineReducers } from '@wordpress/data';
import type {
	Action,
	CatalogState,
	FeaturesState,
	LegacyLicensesState,
	LicenseState,
} from './types';

export const reducer = combineReducers({ features, license, catalog, legacyLicenses });

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
// Legacy licenses
// ---------------------------------------------------------------------------

const LEGACY_LICENSES_DEFAULT: LegacyLicensesState = {
	bySlug: {},
};

function legacyLicenses(
	state: LegacyLicensesState = LEGACY_LICENSES_DEFAULT,
	action: Action
): LegacyLicensesState {
	switch (action.type) {
		case 'RECEIVE_LEGACY_LICENSES': {
			return {
				...state,
				bySlug: Object.fromEntries(
					action.licenses.map((l) => [l.slug, l])
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
	updating: {},
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

		case 'UPDATE_FEATURE_START': {
			const { [action.slug]: _, ...remainingErrors } = state.errorBySlug;
			return {
				...state,
				updating: { ...state.updating, [action.slug]: true },
				errorBySlug: remainingErrors,
			};
		}

		case 'UPDATE_FEATURE_FINISHED': {
			const { slug } = action.feature;
			const { [slug]: _, ...remainingUpdating } = state.updating;
			return {
				...state,
				bySlug: {
					...state.bySlug,
					[slug]: action.feature,
				},
				updating: remainingUpdating,
			};
		}

		case 'UPDATE_FEATURE_FAILED': {
			const { [action.slug]: _, ...remainingUpdating } = state.updating;
			return {
				...state,
				updating: remainingUpdating,
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
	license: { key: null, products: [] },
	isStoring: false,
	isDeleting: false,
	isValidating: false,
	storeError: null,
	deleteError: null,
	validateError: null,
};

function license(
	state: LicenseState = LICENSE_DEFAULT,
	action: Action
): LicenseState {
	switch (action.type) {
		case 'RECEIVE_LICENSE': {
			return {
				...state,
				license: action.license,
			};
		}

		case 'STORE_LICENSE_START': {
			return {
				...state,
				isStoring: true,
				storeError: null,
			};
		}

		case 'STORE_LICENSE_FINISHED': {
			return {
				...state,
				isStoring: false,
				license: action.license,
			};
		}

		case 'STORE_LICENSE_FAILED': {
			return {
				...state,
				isStoring: false,
				storeError: action.error,
			};
		}

		case 'DELETE_LICENSE_START': {
			return {
				...state,
				isDeleting: true,
				deleteError: null,
			};
		}

		case 'DELETE_LICENSE_FINISHED': {
			return {
				...state,
				isDeleting: false,
				license: { key: null, products: [] },
			};
		}

		case 'DELETE_LICENSE_FAILED': {
			return {
				...state,
				isDeleting: false,
				deleteError: action.error,
			};
		}

		case 'VALIDATE_PRODUCT_START': {
			return {
				...state,
				isValidating: true,
				validateError: null,
			};
		}

		case 'VALIDATE_PRODUCT_FINISHED': {
			return {
				...state,
				isValidating: false,
				license: action.license,
			};
		}

		case 'VALIDATE_PRODUCT_FAILED': {
			return {
				...state,
				isValidating: false,
				validateError: action.error,
			};
		}

		default:
			return state;
	}
}
