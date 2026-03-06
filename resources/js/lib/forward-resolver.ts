/**
 * Utilities for @wordpress/data stores.
 *
 * Ported from sync-saas @utils/data/forward-resolver.js
 *
 * @package StellarWP\Uplink
 */

// eslint-disable-next-line @typescript-eslint/no-explicit-any
type AnyResolver = ( ...args: any[] ) => any;

/**
 * Forwards resolution to another resolver with the same arguments.
 *
 * Use when the source and target selectors share the same signature.
 */
export function forwardResolver( resolverName: string ): AnyResolver {
	return ( ...args: unknown[] ) =>
		async ( { resolveSelect }: Record<string, any> ) => {
			await resolveSelect[ resolverName ]( ...args );
		};
}

/**
 * Forwards resolution to another resolver, discarding arguments.
 *
 * Use when a derived selector (e.g. getFeature(slug)) depends on data
 * fetched by a resolver that takes no arguments (e.g. getFeatures()).
 */
export function forwardResolverWithoutArgs( resolverName: string ): AnyResolver {
	return () =>
		async ( { resolveSelect }: Record<string, any> ) => {
			await resolveSelect[ resolverName ]();
		};
}
