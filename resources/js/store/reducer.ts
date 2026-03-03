/**
 * Reducer for the stellarwp/uplink @wordpress/data store.
 *
 * @see .plans/wp-data-store-features.md
 * @package StellarWP\Uplink
 */
import type { Action } from './actions';
import type { Feature } from '@/types/api';

export interface State {
    /** Feature objects keyed by slug — populated by the getFeatures resolver */
    features: Record<string, Feature>;
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
        case 'RECEIVE_FEATURES':
            return {
                ...state,
                features: Object.fromEntries( action.features.map( ( f ) => [ f.slug, f ] ) ),
            };
        case 'PATCH_FEATURE':
            return {
                ...state,
                features: {
                    ...state.features,
                    [ action.slug ]: { ...state.features[ action.slug ], is_enabled: action.enabled },
                },
            };
        default:
            return state;
    }
}
