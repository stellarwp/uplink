/**
 * Shared types for the stellarwp/uplink @wordpress/data store.
 *
 * @package StellarWP\Uplink
 */
import type {
	ReduxStoreConfig,
	StoreDescriptor,
} from '@wordpress/data';
import type { Thunk as BaseThunk } from '@/types/data';

import type UplinkError from '@/errors/uplink-error';
import type { Feature, LicenseProduct, ProductCatalog } from '@/types/api';

import type * as actions from './actions';
import type * as selectors from './selectors';

// ---------------------------------------------------------------------------
// State
// ---------------------------------------------------------------------------

export interface CatalogState {
	/**
	 * Product catalogs keyed by product slug, populated by the getCatalog resolver.
	 */
	byProductSlug: Record<string, ProductCatalog>;
}

export interface FeaturesState {
	/**
	 * Feature objects keyed by slug, populated by the getFeatures resolver.
	 */
	bySlug: Record<string, Feature>;
	/**
	 * Feature slugs currently being toggled.
	 */
	toggling: Record<string, boolean>;
	/**
	 * Per-feature errors from toggle failures, keyed by slug.
	 */
	errorBySlug: Record<string, UplinkError>;
}

export interface LicenseState {
	/**
	 * The stored unified license key, or null if none is set.
	 */
	key: string | null;
	/**
	 * Licensed products associated with this key.
	 */
	products: LicenseProduct[];
	/**
	 * Whether a license store (activation) is in progress.
	 */
	isStoring: boolean;
	/**
	 * Whether a license deletion is in progress.
	 */
	isDeleting: boolean;
	/**
	 * Whether a per-product validation is in progress.
	 */
	isValidating: boolean;
	/**
	 * The error from the last failed license store.
	 * Cleared when a new store starts.
	 */
	storeError: UplinkError | null;
	/**
	 * The error from the last failed license deletion.
	 * Cleared when a new deletion starts.
	 */
	deleteError: UplinkError | null;
	/**
	 * The error from the last failed product validation.
	 * Cleared when a new validation starts.
	 */
	validateError: UplinkError | null;
}

export interface State {
	features: FeaturesState;
	license: LicenseState;
	catalog: CatalogState;
}

// ---------------------------------------------------------------------------
// Actions
// ---------------------------------------------------------------------------

export type Action =
	| { type: 'RECEIVE_CATALOG'; catalogs: ProductCatalog[] }
	| { type: 'RECEIVE_FEATURES'; features: Feature[] }
	| { type: 'TOGGLE_FEATURE_START'; slug: string }
	| { type: 'TOGGLE_FEATURE_FINISHED'; feature: Feature }
	| { type: 'TOGGLE_FEATURE_FAILED'; slug: string; error: UplinkError }
	| { type: 'RECEIVE_LICENSE'; key: string | null; products: LicenseProduct[] }
	| { type: 'STORE_LICENSE_START' }
	| { type: 'STORE_LICENSE_FINISHED'; key: string; products: LicenseProduct[] }
	| { type: 'STORE_LICENSE_FAILED'; error: UplinkError }
	| { type: 'DELETE_LICENSE_START' }
	| { type: 'DELETE_LICENSE_FINISHED' }
	| { type: 'DELETE_LICENSE_FAILED'; error: UplinkError }
	| { type: 'VALIDATE_PRODUCT_START' }
	| { type: 'VALIDATE_PRODUCT_FINISHED' }
	| { type: 'VALIDATE_PRODUCT_FAILED'; error: UplinkError };

// ---------------------------------------------------------------------------
// Thunk
// ---------------------------------------------------------------------------

type Store = StoreDescriptor<
	ReduxStoreConfig<State, typeof actions, typeof selectors>
>;

export type Thunk<T = void> = BaseThunk<Action, Store, T>;
