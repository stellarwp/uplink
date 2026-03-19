/**
 * Types for the useResolvableSelect hook.
 *
 * Ported from sync-saas and extended with error support.
 *
 * @package StellarWP\Uplink
 */
import type { DependencyList } from 'react';

export type Status = 'IDLE' | 'RESOLVING' | 'ERROR' | 'SUCCESS';

export interface ResolvableSelectResponse<Data> {
	/** The requested selector return value. */
	data: Data;

	/** The status of the resolution. */
	status: Status;

	/** The error thrown by the resolver, if any. */
	error: unknown;

	/** Is the record still being resolved? */
	isResolving: boolean;

	/** Was the resolution started? */
	hasStarted: boolean;

	/** Has the resolution finished (success or error)? */
	hasResolved: boolean;
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
type AnySelectors = Record<string, ( ...args: any[] ) => any>;

export type EnrichedSelectors<S extends AnySelectors = AnySelectors> = {
	[K in keyof S]: (
		...args: Parameters<S[K]>
	) => ResolvableSelectResponse<ReturnType<S[K]>>;
};

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export type ResolveFunction = ( store: any ) => EnrichedSelectors;

export type MapResolvableSelect<T> = (
	resolve: ResolveFunction,
	registry: unknown,
) => T;

export type UseResolvableSelect = <T>(
	mapResolvableSelect: MapResolvableSelect<T>,
	deps: DependencyList,
) => T;
