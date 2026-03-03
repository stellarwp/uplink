/**
 * Forward a resolver to another resolver by name.
 *
 * Useful when multiple selectors share a single data-fetching resolver.
 * For example, `getFeaturesByGroup` can forward to the `getFeatures` resolver
 * so both selectors trigger the same fetch.
 *
 * @package StellarWP\Uplink
 */
export default function forwardResolver( resolverName: string ) {
	return ( ...args: unknown[] ) =>
		async ( { resolveSelect }: { resolveSelect: Record< string, ( ...a: unknown[] ) => Promise< unknown > > } ) => {
			await resolveSelect[ resolverName ]( ...args );
		};
}
