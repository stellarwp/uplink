/**
 * Shared thunk types for @wordpress/data stores.
 *
 * Ported from sync-saas @utils/data/types.ts
 *
 * @package StellarWP\Uplink
 */
import type {
	ActionCreatorsOf as BaseActionCreatorsOf,
	AnyConfig,
	ConfigOf,
	CurriedSelectorsOf,
	CurriedSelectorsResolveOf,
	StoreDescriptor,
} from '@wordpress/data';
import type { Action } from 'redux';

// ---------------------------------------------------------------------------
// Metadata action types (invalidateResolution, etc.)
// ---------------------------------------------------------------------------

type MetadataActionCreators = {
	invalidateResolution: ( selectorName: string, args: unknown[] ) => {
		readonly type: 'INVALIDATE_RESOLUTION';
		readonly selectorName: string;
		readonly args: unknown[];
	};
	invalidateResolutionForStore: () => {
		readonly type: 'INVALIDATE_RESOLUTION_FOR_STORE';
	};
	invalidateResolutionForStoreSelector: ( selectorName: string ) => {
		readonly type: 'INVALIDATE_RESOLUTION_FOR_STORE_SELECTOR';
		readonly selectorName: string;
	};
};

type MetadataAction =
	| ReturnType< MetadataActionCreators[ 'invalidateResolution' ] >
	| ReturnType< MetadataActionCreators[ 'invalidateResolutionForStore' ] >
	| ReturnType< MetadataActionCreators[ 'invalidateResolutionForStoreSelector' ] >;

// ---------------------------------------------------------------------------
// Action creators (includes metadata)
// ---------------------------------------------------------------------------

type ActionCreatorsOf< C extends AnyConfig > = BaseActionCreatorsOf< C > &
	MetadataActionCreators;

// ---------------------------------------------------------------------------
// Dispatch / Select / Registry
// ---------------------------------------------------------------------------

export type DispatchFunction< A extends Action > = (
	action: A | MetadataAction
) => void;

export type Registry = {
	dispatch: < S extends string | StoreDescriptor< AnyConfig > >(
		storeNameOrDescriptor: S
	) => S extends StoreDescriptor< AnyConfig >
		? ActionCreatorsOf< ConfigOf< S > >
		: unknown;
	select: < S extends string | StoreDescriptor< AnyConfig > >(
		storeNameOrDescriptor: S
	) => S extends StoreDescriptor< AnyConfig >
		? CurriedSelectorsOf< S >
		: unknown;
	resolveSelect: < S extends string | StoreDescriptor< AnyConfig > >(
		storeNameOrDescriptor: S
	) => S extends StoreDescriptor< AnyConfig >
		? CurriedSelectorsResolveOf< S >
		: unknown;
};

// ---------------------------------------------------------------------------
// ThunkArgs & Thunk
// ---------------------------------------------------------------------------

export type ThunkArgs<
	A extends Action,
	S extends StoreDescriptor< AnyConfig >,
> = {
	dispatch: ( S extends StoreDescriptor< AnyConfig >
		? ActionCreatorsOf< ConfigOf< S > >
		: unknown ) &
		DispatchFunction< A >;
	select: CurriedSelectorsOf< S >;
	resolveSelect: CurriedSelectorsResolveOf< S >;
	registry: Registry;
};

export type Thunk<
	A extends Action,
	S extends StoreDescriptor< AnyConfig >,
	T extends unknown = void,
> = [T] extends [Awaited< infer R >]
	? ( args: ThunkArgs< A, S > ) => Promise< R >
	: ( args: ThunkArgs< A, S > ) => T;
