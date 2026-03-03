/**
 * Selectors for the stellarwp/uplink @wordpress/data store.
 *
 * Extended per phase:
 *   Phase 2 — getFeatures, getFeaturesByGroup, getFeature
 *   Phase 3 — isFeatureEnabled, getFeatureError
 *
 * @see .plans/wp-data-store-features.md
 * @package StellarWP\Uplink
 */
import type { State } from './reducer';

export const selectors = {
    getError: ( state: State, key: string ): string | null => state.errors[ key ] ?? null,
};
