/**
 * Selectors for the stellarwp/uplink @wordpress/data store.
 *
 * @see .plans/wp-data-store-features.md
 * @package StellarWP\Uplink
 */
import type { State } from './reducer';
import type { Feature } from '@/types/api';

export const selectors = {
    getError: ( state: State, key: string ): string | null =>
        state.errors[ key ] ?? null,

    getFeatures: ( state: State ): Feature[] =>
        Object.values( state.features ),

    getFeaturesByGroup: ( state: State, group: string ): Feature[] =>
        Object.values( state.features ).filter( ( f ) => f.group === group ),

    getFeature: ( state: State, slug: string ): Feature | null =>
        state.features[ slug ] ?? null,

    isFeatureEnabled: ( state: State, slug: string ): boolean =>
        state.features[ slug ]?.is_enabled ?? false,

    getFeatureError: ( state: State, slug: string ): string | null =>
        state.errors[ `feature:${ slug }` ] ?? null,
};
