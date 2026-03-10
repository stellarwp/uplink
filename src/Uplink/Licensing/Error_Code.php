<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Licensing;

/**
 * WP_Error codes for the Licensing system.
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

	/**
	 * The license key could not be stored.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const STORE_FAILED = 'stellarwp-uplink-store-failed';

	/**
	 * The subscription has expired.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const EXPIRED = 'stellarwp-uplink-expired';

	/**
	 * The subscription is suspended.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const SUSPENDED = 'stellarwp-uplink-suspended';

	/**
	 * The subscription is cancelled.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const CANCELLED = 'stellarwp-uplink-cancelled';

	/**
	 * The license is suspended (all products affected).
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const LICENSE_SUSPENDED = 'stellarwp-uplink-license-suspended';

	/**
	 * The license is banned (all products affected).
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const LICENSE_BANNED = 'stellarwp-uplink-license-banned';

	/**
	 * No subscription exists for this product under the license.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const NO_SUBSCRIPTION = 'stellarwp-uplink-no-subscription';

	/**
	 * All activation seats are in use.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const OUT_OF_ACTIVATIONS = 'stellarwp-uplink-out-of-activations';

	/**
	 * An unexpected or unrecognized error occurred.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const UNKNOWN_ERROR = 'stellarwp-uplink-unknown-error';

	/**
	 * Maps an error code to its recommended HTTP status code.
	 *
	 * @since 3.0.0
	 *
	 * @param string $code An Error_Code constant value.
	 *
	 * @return int The HTTP status code (defaults to 422 for unknown codes).
	 */
	public static function http_status( string $code ): int {
		/** @var array<string, int> */
		static $map = [
			// 400 Bad Request — the key format is invalid.
			self::INVALID_KEY          => 400,

			// 422 Unprocessable Entity — the request was understood but the
			// license state prevents the operation from completing.
			self::PRODUCT_NOT_FOUND    => 422,
			self::EXPIRED              => 422,
			self::SUSPENDED            => 422,
			self::CANCELLED            => 422,
			self::LICENSE_SUSPENDED    => 422,
			self::LICENSE_BANNED       => 422,
			self::NO_SUBSCRIPTION      => 422,
			self::OUT_OF_ACTIVATIONS   => 422,

			// 500 Internal Server Error — storage failure.
			self::STORE_FAILED         => 500,

			// 500 Internal Server Error — unexpected or unrecognized error.
			self::UNKNOWN_ERROR        => 500,

			// 502 Bad Gateway — upstream service returned an invalid response.
			self::INVALID_RESPONSE     => 502,
		];

		return $map[ $code ] ?? 422;
	}
}
