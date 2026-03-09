<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Licensing\Enums;

use StellarWP\Uplink\Licensing\Error_Code;

/**
 * Validation status constants mirroring the v4 licensing API.
 *
 * @since 3.0.0
 *
 * @see \StellarWP\Licensing\V4\Domain\Enums\Validation_Status (licensing service)
 */
final class Validation_Status {

	/**
	 * The license is valid and the product is activated on this domain.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const VALID = 'valid';

	/**
	 * The subscription has expired.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const EXPIRED = 'expired';

	/**
	 * The subscription is suspended.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const SUSPENDED = 'suspended';

	/**
	 * The subscription is cancelled.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const CANCELLED = 'cancelled';

	/**
	 * The license itself is suspended (all products affected).
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const LICENSE_SUSPENDED = 'license_suspended';

	/**
	 * The license is banned (all products affected).
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const LICENSE_BANNED = 'license_banned';

	/**
	 * No subscription exists for this product under the license.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const NO_SUBSCRIPTION = 'no_subscription';

	/**
	 * The product is not activated on this domain.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const NOT_ACTIVATED = 'not_activated';

	/**
	 * All available activation seats are consumed.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const OUT_OF_ACTIVATIONS = 'out_of_activations';

	/**
	 * The license key is not recognized.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const INVALID_KEY = 'invalid_key';

	/**
	 * Returns all valid status values.
	 *
	 * @since 3.0.0
	 *
	 * @return string[]
	 */
	public static function all(): array {
		return [
			self::VALID,
			self::EXPIRED,
			self::SUSPENDED,
			self::CANCELLED,
			self::LICENSE_SUSPENDED,
			self::LICENSE_BANNED,
			self::NO_SUBSCRIPTION,
			self::NOT_ACTIVATED,
			self::OUT_OF_ACTIVATIONS,
			self::INVALID_KEY,
		];
	}

	/**
	 * Returns whether the given value is a valid status.
	 *
	 * @since 3.0.0
	 *
	 * @param string $value The status value to check.
	 *
	 * @return bool
	 */
	public static function is_valid( string $value ): bool {
		return in_array( $value, self::all(), true );
	}

	/**
	 * Returns a human-readable error message for a non-valid status.
	 *
	 * @since 3.0.0
	 *
	 * @param string $value The status value.
	 *
	 * @return string
	 */
	public static function message( string $value ): string {
		$messages = [
			self::EXPIRED            => __( 'The subscription has expired.', '%TEXTDOMAIN%' ),
			self::SUSPENDED          => __( 'The subscription is suspended.', '%TEXTDOMAIN%' ),
			self::CANCELLED          => __( 'The subscription is cancelled.', '%TEXTDOMAIN%' ),
			self::LICENSE_SUSPENDED  => __( 'The license is suspended.', '%TEXTDOMAIN%' ),
			self::LICENSE_BANNED     => __( 'The license is banned.', '%TEXTDOMAIN%' ),
			self::NO_SUBSCRIPTION    => __( 'No subscription exists for this product.', '%TEXTDOMAIN%' ),
			self::NOT_ACTIVATED      => __( 'The product is not activated on this domain.', '%TEXTDOMAIN%' ),
			self::OUT_OF_ACTIVATIONS => __( 'All activation seats are in use.', '%TEXTDOMAIN%' ),
			self::INVALID_KEY        => __( 'The license key is not recognized.', '%TEXTDOMAIN%' ),
		];

		return $messages[ $value ] ?? __( 'The license validation failed.', '%TEXTDOMAIN%' );
	}

	/**
	 * Maps a validation status to its corresponding Error_Code constant.
	 *
	 * @since 3.0.0
	 *
	 * @param string $value The validation status value.
	 *
	 * @return string An Error_Code constant value.
	 */
	public static function error_code( string $value ): string {
		$map = [
			self::EXPIRED            => Error_Code::EXPIRED,
			self::SUSPENDED          => Error_Code::SUSPENDED,
			self::CANCELLED          => Error_Code::CANCELLED,
			self::LICENSE_SUSPENDED  => Error_Code::LICENSE_SUSPENDED,
			self::LICENSE_BANNED     => Error_Code::LICENSE_BANNED,
			self::NO_SUBSCRIPTION    => Error_Code::NO_SUBSCRIPTION,
			self::OUT_OF_ACTIVATIONS => Error_Code::OUT_OF_ACTIVATIONS,
			self::INVALID_KEY        => Error_Code::INVALID_KEY,
		];

		return $map[ $value ] ?? Error_Code::INVALID_RESPONSE;
	}
}
