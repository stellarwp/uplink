/**
 * Registers the stellarwp/uplink @wordpress/data store.
 *
 * Call registerUplinkStore() once before createRoot() in index.tsx.
 * Consumers import STORE_NAME and use useSelect / useDispatch.
 *
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

// ---------------------------------------------------------------------------
// Type augmentation — teaches useSelect / useDispatch about this store.
// ---------------------------------------------------------------------------

/** Selector interface exposed to useSelect consumers. */
export type StoreSelectors = typeof selectors & {
    /** @wordpress/data meta-selector — true once the resolver has finished. */
    hasFinishedResolution: ( selectorName: string, args?: unknown[] ) => boolean;
    /** @wordpress/data meta-selector — true while the resolver is running. */
    isResolving: ( selectorName: string, args?: unknown[] ) => boolean;
};

/** Dispatch interface exposed to useDispatch consumers. */
export type StoreDispatch = typeof actions & {
    /** @wordpress/data meta-dispatch — invalidates a resolver so it re-runs. */
    invalidateResolution: ( selectorName: string, args?: unknown[] ) => void;
};

declare module '@wordpress/data' {
    // eslint-disable-next-line @typescript-eslint/no-shadow
    function select( storeName: typeof STORE_NAME ): StoreSelectors;
    // eslint-disable-next-line @typescript-eslint/no-shadow
    function dispatch( storeName: typeof STORE_NAME ): StoreDispatch;
}
