<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Licensing;

/**
 * WP_Error codes for the Licensing system.
 *
 * PHP 7.4 does not support native enums, so string
 * constants serve as the next-best compile-time guard.
 *
 * @since 3.0.0
 */
final class Error_Code {

	/**
	 * The license key is not recognized.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const INVALID_KEY = 'stellarwp-uplink-invalid-key';

	/**
	 * The license response could not be decoded.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const INVALID_RESPONSE = 'stellarwp-uplink-invalid-response';

	/**
	 * The requested product slug was not found in the catalog.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const PRODUCT_NOT_FOUND = 'stellarwp-uplink-product-not-found';
}
