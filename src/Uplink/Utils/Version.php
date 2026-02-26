<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Utils;

use StellarWP\Uplink\Uplink;

/**
 * Cross-instance version leadership utility.
 *
 * When multiple vendor-prefixed copies of Uplink are active, only the
 * highest version should own shared responsibilities (admin page, REST
 * routes, etc.). This class centralises that check using a global
 * WordPress action as the cross-copy mutex.
 *
 * @since 3.0.0
 */
class Version {

	/**
	 * Determines whether this Uplink instance is the highest active version.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public static function is_highest(): bool {
		$highest = (string) apply_filters( 'stellarwp/uplink/highest_version', '0.0.0' );

		return ! version_compare( Uplink::VERSION, $highest, '<' );
	}

	/**
	 * Determines whether this Uplink instance should handle the given
	 * action, and if so, claims it so no other instance can.
	 *
	 * @since 3.0.0
	 *
	 * @param string $action A short, unique identifier for the responsibility
	 *                       (e.g. 'admin_page', 'rest_routes').
	 *
	 * @return bool True if this instance should handle the action.
	 */
	public static function should_handle( string $action ): bool {
		if ( ! self::is_highest() ) {
			return false;
		}

		$hook = 'stellarwp/uplink/handled/' . $action;

		if ( did_action( $hook ) ) {
			return false;
		}

		do_action( $hook );

		return true;
	}
}
