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
import type { Feature } from '@/types/api';

import type * as actions from './actions';
import type * as selectors from './selectors';

// ---------------------------------------------------------------------------
// State
// ---------------------------------------------------------------------------

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
	 * Whether a license activation is in progress.
	 */
	isActivating: boolean;
	/**
	 * Whether a license deletion is in progress.
	 */
	isDeleting: boolean;
	/**
	 * The error from the last failed license activation.
	 * Cleared when a new activation starts.
	 */
	activateError: UplinkError | null;
	/**
	 * The error from the last failed license deletion.
	 * Cleared when a new deletion starts.
	 */
	deleteError: UplinkError | null;
}

export interface State {
	features: FeaturesState;
	license: LicenseState;
}

// ---------------------------------------------------------------------------
// Actions
// ---------------------------------------------------------------------------

export type Action =
	| { type: 'RECEIVE_FEATURES'; features: Feature[] }
	| { type: 'TOGGLE_FEATURE_START'; slug: string }
	| { type: 'TOGGLE_FEATURE_FINISHED'; feature: Feature }
	| { type: 'TOGGLE_FEATURE_FAILED'; slug: string; error: UplinkError }
	| { type: 'RECEIVE_LICENSE'; key: string | null }
	| { type: 'ACTIVATE_LICENSE_START' }
	| { type: 'ACTIVATE_LICENSE_FINISHED'; key: string }
	| { type: 'ACTIVATE_LICENSE_FAILED'; error: UplinkError }
	| { type: 'DELETE_LICENSE_START' }
	| { type: 'DELETE_LICENSE_FINISHED' }
	| { type: 'DELETE_LICENSE_FAILED'; error: UplinkError };

// ---------------------------------------------------------------------------
// Thunk
// ---------------------------------------------------------------------------

type Store = StoreDescriptor<
	ReduxStoreConfig<State, typeof actions, typeof selectors>
>;

export type Thunk<T = void> = BaseThunk<Action, Store, T>;
