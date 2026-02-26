<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Legacy;

/**
 * A single unified license key that covers all StellarWP products.
 *
 * When present, Uplink takes over license management and legacy
 * per-product systems can be suppressed.
 *
 * @since 3.1.0
 */
class UnifiedKey {

	const OPTION_NAME = 'stellarwp_uplink_unified_key';

	/**
	 * Get the unified key.
	 *
	 * @since 3.1.0
	 *
	 * @return string
	 */
	public static function get(): string {
		return (string) get_option( self::OPTION_NAME, '' );
	}

	/**
	 * Set the unified key.
	 *
	 * @since 3.1.0
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public static function set( string $key ): bool {
		return update_option( self::OPTION_NAME, $key, false );
	}

	/**
	 * Delete the unified key.
	 *
	 * @since 3.1.0
	 *
	 * @return bool
	 */
	public static function delete(): bool {
		return delete_option( self::OPTION_NAME );
	}

	/**
	 * Whether a unified key exists.
	 *
	 * @since 3.1.0
	 *
	 * @return bool
	 */
	public static function exists(): bool {
		return self::get() !== '';
	}
}
