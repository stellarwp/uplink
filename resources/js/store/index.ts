/**
 * Registers the stellarwp/uplink @wordpress/data store.
 *
 * Call registerUplinkStore() once before createRoot() in index.tsx.
 * Consumers import the store descriptor and use useSelect / useDispatch.
 *
 * @package StellarWP\Uplink
 */
import { createReduxStore, register } from '@wordpress/data';
import reducer, { initializeDefaultState } from './reducer';
import * as actions from './actions';
import * as selectors from './selectors';
import * as resolvers from './resolvers';
import { STORE_NAME } from './constants';

export const store = createReduxStore( STORE_NAME, {
	reducer,
	actions,
	selectors,
	resolvers,
	initialState: initializeDefaultState(),
} );

export function registerUplinkStore(): void {
	register( store );
}

export { STORE_NAME };
