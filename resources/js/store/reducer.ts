/**
 * Reducer for the stellarwp/uplink @wordpress/data store.
 *
 * State slices are added per phase:
 *   Phase 2 — features (Record<string, Feature>)
 *
 * @see .plans/wp-data-store-features.md
 * @package StellarWP\Uplink
 */
import type { Action } from './actions';

export interface State {
    /** Feature objects keyed by slug — populated in Phase 2 */
    features: Record<string, unknown>;
    /** Action-scoped error messages, e.g. `feature:give-stripe-gateway` */
    errors:   Record<string, string>;
}

const DEFAULT_STATE: State = {
    features: {},
    errors:   {},
};

export function reducer( state: State = DEFAULT_STATE, action: Action ): State {
    switch ( action.type ) {
        case 'SET_ERROR':
            return { ...state, errors: { ...state.errors, [ action.key ]: action.message } };
        case 'CLEAR_ERROR': {
            const { [ action.key ]: _, ...rest } = state.errors;
            return { ...state, errors: rest };
        }
        default:
            return state;
    }
}
