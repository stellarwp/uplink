/**
 * Registers the stellarwp/uplink @wordpress/data store.
 *
 * Call registerUplinkStore() once before createRoot() in index.tsx.
 * Consumers import STORE_NAME and use useSelect / useDispatch.
 *
 * @see .plans/wp-data-store-features.md
 * @package StellarWP\Uplink
 */
import { createReduxStore, register } from '@wordpress/data';
import { reducer }   from './reducer';
import { actions }   from './actions';
import { selectors } from './selectors';
import { resolvers } from './resolvers';
import { STORE_NAME } from './constants';

export const store = createReduxStore( STORE_NAME, {
    reducer,
    actions,
    selectors,
    resolvers,
} );

export function registerUplinkStore(): void {
    register( store );
}

export { STORE_NAME };
