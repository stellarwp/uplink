/**
 * Like useSelect, but selectors return objects containing
 * both the original data AND the resolution info.
 *
 * Ported from sync-saas and converted to TypeScript.
 *
 * Inspired by `@wordpress/core-data` `useQuerySelect`.
 *
 * @see https://github.com/WordPress/gutenberg/blob/c97c26fe371e3d40efe197d8f398326a16cdbf46/packages/core-data/src/hooks/use-query-select.ts
 *
 * @package StellarWP\Uplink
 */
import type { DependencyList } from 'react';
import { useSelect } from '@wordpress/data';
import type {
	EnrichedSelectors,
	MapResolvableSelect,
	ResolvableSelectResponse,
	Status,
} from './types';

/**
 * Meta selectors added by @wordpress/data that should not be enriched.
 */
const META_SELECTORS = [
	'getIsResolving',
	'hasStartedResolution',
	'hasFinishedResolution',
	'isResolving',
	'getCachedResolvers',
];

/**
 * Cache enriched selector proxies by selector object identity so we
 * don't recreate them on every useSelect call within the same render.
 */
// eslint-disable-next-line @typescript-eslint/no-explicit-any
const cache = new WeakMap<object, EnrichedSelectors<any>>();

/**
 * Wrap store selectors so each call returns a {@link ResolvableSelectResponse}
 * with the original data and resolution metadata.
 */
// eslint-disable-next-line @typescript-eslint/no-explicit-any
function enrichSelectors( selectors: Record<string, any> ): EnrichedSelectors {
	const cached = cache.get( selectors );
	if ( cached ) {
		return cached;
	}

	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	const resolvers: Record<string, any> = {};

	for ( const selectorName in selectors ) {
		if ( META_SELECTORS.includes( selectorName ) ) {
			continue;
		}

		Object.defineProperty( resolvers, selectorName, {
			get:
				() =>
				( ...args: unknown[] ): ResolvableSelectResponse<unknown> => {
					const data = selectors[ selectorName ]( ...args );
					const resolutionState = selectors.getResolutionState(
						selectorName,
						args,
					);
					const resolutionStatus: string | undefined =
						resolutionState?.status;

					let status: Status;
					switch ( resolutionStatus ) {
						case 'resolving':
							status = 'RESOLVING';
							break;
						case 'finished':
							status = 'SUCCESS';
							break;
						case 'error':
							status = 'ERROR';
							break;
						default:
							status = 'IDLE';
					}

					return {
						data,
						status,
						error: resolutionState?.error ?? null,
						isResolving: status === 'RESOLVING',
						hasStarted: status !== 'IDLE',
						hasResolved:
							status === 'SUCCESS' || status === 'ERROR',
					};
				},
		} );
	}

	cache.set( selectors, resolvers as EnrichedSelectors );
	return resolvers as EnrichedSelectors;
}

/**
 * Like useSelect, but the selectors return objects containing
 * both the original data AND the resolution info.
 */
export default function useResolvableSelect<T>(
	mapResolvableSelect: MapResolvableSelect<T>,
	deps: DependencyList,
): T {
	return useSelect(
		( select, registry ) => {
			// eslint-disable-next-line @typescript-eslint/no-explicit-any
			const resolve = ( store: any ) =>
				enrichSelectors( select( store ) );
			return mapResolvableSelect( resolve, registry );
		},
		// eslint-disable-next-line react-hooks/exhaustive-deps
		deps as unknown[],
	);
}
