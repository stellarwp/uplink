/**
 * Type definitions for the stellarwp/uplink @wordpress/data store.
 *
 * @package StellarWP\Uplink
 */
import type { StoreDescriptor, ReduxStoreConfig } from '@wordpress/data';
import type { Feature } from '@/types/api';
import type UplinkError from '@/errors/uplink-error';
import type { Thunk } from '@/utils/data/types';

import type * as actions from './actions';
import type * as selectors from './selectors';

// ---------------------------------------------------------------------------
// Named action types
// ---------------------------------------------------------------------------

export type ReceiveFeatures = { type: 'RECEIVE_FEATURES'; features: Feature[] };

export type PatchFeatureStart = { type: 'PATCH_FEATURE_START'; slug: string; enabled: boolean };
export type PatchFeatureFinished = { type: 'PATCH_FEATURE_FINISHED'; feature: Feature };
export type PatchFeatureFailed = { type: 'PATCH_FEATURE_FAILED'; slug: string; enabled: boolean; error: UplinkError };

export type Action =
	| ReceiveFeatures
	| PatchFeatureStart
	| PatchFeatureFinished
	| PatchFeatureFailed;

// ---------------------------------------------------------------------------
// State (combineReducers shape)
// ---------------------------------------------------------------------------

export type Features = {
	bySlug: Record< string, Feature >;
	isUpdating: Record< string, boolean >;
	errorBySlug: Record< string, UplinkError >;
};

export type State = {
	features: Features;
};

// ---------------------------------------------------------------------------
// Module thunk alias
// ---------------------------------------------------------------------------

export type UplinkThunk< T = void > = Thunk<
	Action,
	StoreDescriptor< ReduxStoreConfig< State, typeof actions, typeof selectors > >,
	T
>;
