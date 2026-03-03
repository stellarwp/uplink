<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Catalog;

/**
 * WP_Error codes for the Catalog system.
 *
 * @since 3.0.0
 */
final class Error_Code {

	/**
	 * The requested product slug was not found in the catalog.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const PRODUCT_NOT_FOUND = 'stellarwp-uplink-catalog-product-not-found';

	/**
	 * The catalog response could not be decoded.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const INVALID_RESPONSE = 'stellarwp-uplink-catalog-invalid-response';
}
