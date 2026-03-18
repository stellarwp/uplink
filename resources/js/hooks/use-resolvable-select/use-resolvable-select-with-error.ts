/**
 * Wrapper around useResolvableSelect that throws resolution errors
 * during render so they are caught by the nearest React ErrorBoundary.
 *
 * @package StellarWP\Uplink
 */
import type { DependencyList } from 'react';
import { __ } from '@wordpress/i18n';
import useResolvableSelect from './use-resolvable-select';
import UplinkError from '@/errors/uplink-error';
import { ErrorCode } from '@/errors/error-code';
import type { MapResolvableSelect, ResolvableSelectResponse } from './types';

/**
 * The consumer must return a record of resolvable results so the hook
 * can inspect each one for errors.
 */
type ResolvableRecord = Record<string, ResolvableSelectResponse<unknown>>;

/**
 * Find the first error among a set of resolvable results and wrap it
 * as an UplinkError.
 */
function findError( results: ResolvableRecord ): UplinkError | null {
	for ( const key in results ) {
		const entry = results[ key ];
		if ( entry.status === 'ERROR' ) {
			return UplinkError.syncFrom(
				entry.error,
				ErrorCode.ResolutionFailed,
				__( 'Liquid Web Software failed to load your data.', '%TEXTDOMAIN%' ),
			);
		}
	}
	return null;
}

/**
 * Like useResolvableSelect, but throws resolution errors during render
 * so they are caught by the nearest React ErrorBoundary.
 *
 * The consumer callback must return a flat object of resolvable results.
 *
 * @throws {UplinkError} When any selector's resolution fails. If the resolver
 *   threw an UplinkError, that exact instance is re-thrown. Otherwise a new
 *   UplinkError with code {@link ErrorCode.ResolutionFailed} is created.
 *
 * @example
 * ```ts
 * const { features, catalog } = useResolvableSelectWithError(
 *     ( resolve ) => ( {
 *         features: resolve( uplinkStore ).getFeatures(),
 *         catalog: resolve( uplinkStore ).getCatalog(),
 *     } ),
 *     [],
 * );
 * ```
 */
export default function useResolvableSelectWithError<
	T extends ResolvableRecord,
>(
	mapResolvableSelect: MapResolvableSelect<T>,
	deps: DependencyList,
): T {
	const result = useResolvableSelect( mapResolvableSelect, deps );

	const found = findError( result );
	if ( found ) {
		throw found;
	}

	return result;
}
