/**
 * UplinkError -- typed wrapper around the WP REST API serialized WP_Error.
 *
 * @wordpress/api-fetch throws the parsed JSON body (a plain object) when
 * the server returns a non-2xx response. UplinkError normalizes that into
 * a proper Error subclass with structured access to code, data, and any
 * additional errors.
 *
 * The entire error chain is typed. `additionalErrors` contains UplinkError
 * instances (not plain WpRestError objects), so consumers get `.code`,
 * `.status`, and `.data` on every entry without casting.
 *
 * @package StellarWP\Uplink
 */

import type { WpRestError } from './types';
import { isWpRestError } from './utils';

export default class UplinkError extends Error {
	/**
	 * Machine-readable error code from the WP_Error.
	 */
	readonly code: string;

	/**
	 * Data payload (usually contains `{ status: number }`).
	 */
	readonly data: Record< string, unknown >;

	/**
	 * Secondary errors from a multi-code WP_Error response. This is a
	 * deserialization concern only. Use `cause` (via `UplinkError.from()`)
	 * to chain errors on the frontend.
	 */
	readonly additionalErrors: UplinkError[];

	/**
	 * Original cause, if this error wraps another.
	 */
	readonly cause?: Error;

	constructor( wpError: WpRestError, options?: { cause?: Error } ) {
		super( wpError.message );
		this.name = 'UplinkError';
		this.code = wpError.code;
		this.data = wpError.data ?? {};
		this.additionalErrors = ( wpError.additional_errors ?? [] ).map(
			( entry ) => new UplinkError( entry ),
		);
		this.cause = options?.cause;
	}

	/**
	 * HTTP status code, if present.
	 */
	get status(): number | undefined {
		return typeof this.data.status === 'number' ? this.data.status : undefined;
	}

	/**
	 * Flatten the error tree into an array. Collects this error, then its
	 * additionalErrors (server-side siblings), then recurses into cause.
	 */
	toArray(): UplinkError[] {
		const result: UplinkError[] = [ this ];
		for ( const additional of this.additionalErrors ) {
			result.push( ...additional.toArray() );
		}
		if ( this.cause instanceof UplinkError ) {
			result.push( ...this.cause.toArray() );
		}
		return result;
	}

	/**
	 * Wrap an unknown caught value with context. The original is preserved
	 * as `cause` so context accumulates as the error moves through layers.
	 *
	 * For hydrating a WpRestError without adding context, use the
	 * constructor directly.
	 */
	static from( error: unknown, code: string, message: string ): UplinkError {
		if ( error instanceof UplinkError || error instanceof Error ) {
			return new UplinkError( { code, message }, { cause: error } );
		}

		if ( isWpRestError( error ) ) {
			return new UplinkError(
				{ code, message, data: error.data, additional_errors: error.additional_errors },
				{ cause: new UplinkError( error ) },
			);
		}

		return new UplinkError( { code, message } );
	}
}
