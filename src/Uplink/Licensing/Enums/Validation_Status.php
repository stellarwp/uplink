<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Licensing\Enums;

/**
 * Validation status constants mirroring the v4 licensing API.
 *
 * PHP 7.4 does not support native enums, so string
 * constants serve as the next-best compile-time guard.
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
}
