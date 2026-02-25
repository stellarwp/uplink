<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Utils;

/**
 * Safe type casting utilities.
 *
 * @since 3.0.0
 */
class Cast {

	/**
	 * Safely casts a value to a string.
	 *
	 * @since 3.0.0
	 *
	 * @param mixed $value The value to cast.
	 *
	 * @return string
	 */
	public static function to_string( $value ): string {
		if ( is_string( $value ) ) {
			return $value;
		}

		if ( ! is_scalar( $value ) ) {
			return '';
		}

		return strval( $value );
	}

	/**
	 * Safely casts a value to a bool.
	 *
	 * @since 3.0.0
	 *
	 * @param mixed $value The value to cast.
	 *
	 * @return bool
	 */
	public static function to_bool( $value ): bool {
		if ( is_bool( $value ) ) {
			return $value;
		}

		if ( ! is_scalar( $value ) ) {
			return false;
		}

		return boolval( $value );
	}
}
