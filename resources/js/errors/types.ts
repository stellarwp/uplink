/**
 * WP REST API error shape.
 *
 * When a WP_Error is returned from a REST endpoint, WordPress serializes it
 * into this JSON structure. @wordpress/api-fetch throws the parsed body
 * as a plain object on non-2xx responses.
 *
 * @package StellarWP\Uplink
 */
export interface WpRestError {
	/**
	 * Machine-readable error code (e.g. "stellarwp-uplink-feature-not-found").
	 */
	code: string;
	/**
	 * Human-readable error message.
	 */
	message: string;
	/**
	 * Optional data, typically includes `status` (HTTP code).
	 */
	data?: { status?: number; [ key: string ]: unknown };
	/**
	 * Additional errors when the WP_Error contains more than one.
	 */
	additional_errors?: WpRestError[];
}
