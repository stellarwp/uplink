/**
 * Reducer for the stellarwp/uplink @wordpress/data store.
 *
 * @package StellarWP\Uplink
 */
import type { Action } from './actions';
import type { Feature } from '@/types/api';

export interface LicenseState {
    /** The stored unified license key, or null if none is set. */
    key:             string | null;
    isActivating:    boolean;
    isDeleting:      boolean;
    activateError:   string | null;
    deleteError:     string | null;
}

const LICENSE_DEFAULT: LicenseState = {
    key:           null,
    isActivating:  false,
    isDeleting:    false,
    activateError: null,
    deleteError:   null,
};

export interface State {
    /** Feature objects keyed by slug — populated by the getFeatures resolver */
    features: Record<string, Feature>;
    /** Action-scoped error messages, e.g. `feature:give-stripe-gateway` */
    errors:   Record<string, string>;
    /** Unified license key state — populated by the getLicense resolver */
    license:  LicenseState;
}

const DEFAULT_STATE: State = {
    features: {},
    errors:   {},
    license:  LICENSE_DEFAULT,
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
        case 'RECEIVE_LICENSE':
            return { ...state, license: { ...state.license, key: action.key } };
        case 'ACTIVATE_LICENSE_START':
            return { ...state, license: { ...state.license, isActivating: true, activateError: null } };
        case 'ACTIVATE_LICENSE_FINISHED':
            return { ...state, license: { ...state.license, isActivating: false, key: action.key } };
        case 'ACTIVATE_LICENSE_FAILED':
            return { ...state, license: { ...state.license, isActivating: false, activateError: action.error } };
        case 'DELETE_LICENSE_START':
            return { ...state, license: { ...state.license, isDeleting: true, deleteError: null } };
        case 'DELETE_LICENSE_FINISHED':
            return { ...state, license: { ...state.license, isDeleting: false, key: null } };
        case 'DELETE_LICENSE_FAILED':
            return { ...state, license: { ...state.license, isDeleting: false, deleteError: action.error } };
        default:
            return state;
    }
}
