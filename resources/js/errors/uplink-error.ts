/**
 * UplinkError — typed wrapper around the WP REST API serialized WP_Error.
 *
 * @wordpress/api-fetch throws the parsed JSON body (a plain object) when
 * the server returns a non-2xx response. UplinkError normalizes that into
 * a proper Error subclass with structured access to code, data, and any
 * additional errors.
 *
 * @package StellarWP\Uplink
 */
import { __ } from '@wordpress/i18n';

import type { WpRestError } from './types';
import { isWpRestError } from './utils';

const DEFAULT_CODE = 'unknown';
const DEFAULT_MESSAGE = __( 'An unknown error occurred.', '%TEXTDOMAIN%' );

export default class UplinkError extends Error {
	/** Machine-readable error code from the WP_Error. */
	readonly code: string;

	/** Data payload (usually contains `{ status: number }`). */
	readonly data: Record< string, unknown >;

	/** Secondary errors when the WP_Error contained more than one. */
	readonly additionalErrors: WpRestError[];

	constructor( wpError: WpRestError, options?: ErrorOptions ) {
		super( wpError.message, options );
		this.name = 'UplinkError';
		this.code = wpError.code;
		this.data = wpError.data ?? {};
		this.additionalErrors = wpError.additional_errors ?? [];
	}

	/** HTTP status code, if present. */
	get status(): number | undefined {
		return typeof this.data.status === 'number' ? this.data.status : undefined;
	}

	/**
	 * Create an UplinkError from an unknown caught value.
	 *
	 * Handles:
	 * - UplinkError (passthrough)
	 * - WP REST serialized WP_Error (plain object with `code` + `message`)
	 * - Standard Error instances
	 * - Arbitrary values (falls back to provided or default code / message)
	 *
	 * @param error   The caught value to normalize.
	 * @param code    Fallback error code when the value cannot be parsed.
	 * @param message Fallback message when the value cannot be parsed.
	 */
	static from(
		error: unknown,
		code: string = DEFAULT_CODE,
		message: string = DEFAULT_MESSAGE,
	): UplinkError {
		if ( error instanceof UplinkError ) {
			return error;
		}

		if ( isWpRestError( error ) ) {
			return new UplinkError( error );
		}

		if ( error instanceof Error ) {
			return new UplinkError( { code, message }, { cause: error } );
		}

		return new UplinkError( { code, message } );
	}
}

