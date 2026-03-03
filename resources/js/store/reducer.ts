/**
 * Reducer for the stellarwp/uplink @wordpress/data store.
 *
 * Uses combineReducers so catalog / licenses can be added alongside features.
 *
 * @package StellarWP\Uplink
 */
import { combineReducers } from '@wordpress/data';
import type { Action, Features, State } from './types';

function features( state: Features, action: Action ): Features {
	switch ( action.type ) {
		case 'RECEIVE_FEATURES':
			return {
				...state,
				bySlug: Object.fromEntries( action.features.map( ( f ) => [ f.slug, f ] ) ),
			};
		case 'PATCH_FEATURE_START': {
			const { [ action.slug ]: _, ...restErrors } = state.errorBySlug;
			return {
				...state,
				bySlug: {
					...state.bySlug,
					[ action.slug ]: { ...state.bySlug[ action.slug ], is_enabled: action.enabled },
				},
				isUpdating: { ...state.isUpdating, [ action.slug ]: true },
				errorBySlug: restErrors,
			};
		}
		case 'PATCH_FEATURE_FINISHED':
			return {
				...state,
				bySlug: { ...state.bySlug, [ action.feature.slug ]: action.feature },
				isUpdating: { ...state.isUpdating, [ action.feature.slug ]: false },
			};
		case 'PATCH_FEATURE_FAILED':
			return {
				...state,
				bySlug: {
					...state.bySlug,
					[ action.slug ]: { ...state.bySlug[ action.slug ], is_enabled: action.enabled },
				},
				isUpdating: { ...state.isUpdating, [ action.slug ]: false },
				errorBySlug: { ...state.errorBySlug, [ action.slug ]: action.error },
			};
		default:
			return state;
	}
}

export default combineReducers( { features } );

export function initializeDefaultState(): State {
	return {
		features: {
			bySlug: {},
			isUpdating: {},
			errorBySlug: {},
		},
	};
}
