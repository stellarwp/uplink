/**
 * Selectors for the stellarwp/uplink @wordpress/data store.
 *
 * @package StellarWP\Uplink
 */
import { createSelector } from '@wordpress/data';
import type UplinkError from '@/errors/uplink-error';
import type { Feature } from '@/types/api';
import type { State } from './types';

// ---------------------------------------------------------------------------
// Updating
// ---------------------------------------------------------------------------

export function isFeatureUpdating( state: State, slug: string ): boolean {
	return state.features.isUpdating[ slug ] ?? false;
}

// ---------------------------------------------------------------------------
// Features — memoized (creates new arrays)
// ---------------------------------------------------------------------------

export const getFeatures = createSelector(
	( state: State ): Feature[] => Object.values( state.features.bySlug ),
	( state: State ) => [ state.features.bySlug ],
);

export const getFeaturesByGroup = createSelector(
	( state: State, group: string ): Feature[] =>
		Object.values( state.features.bySlug ).filter( ( f ) => f.group === group ),
	( state: State, group: string ) => [ state.features.bySlug, group ],
);

// ---------------------------------------------------------------------------
// Features — direct state access (stable references)
// ---------------------------------------------------------------------------

export function getFeature( state: State, slug: string ): Feature | null {
	return state.features.bySlug[ slug ] ?? null;
}

export function isFeatureEnabled( state: State, slug: string ): boolean {
	return state.features.bySlug[ slug ]?.is_enabled ?? false;
}

// ---------------------------------------------------------------------------
// Errors
// ---------------------------------------------------------------------------

export function getFeatureError( state: State, slug: string ): UplinkError | null {
	return state.features.errorBySlug[ slug ] ?? null;
}
