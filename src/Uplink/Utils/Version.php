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
	 * Determines whether this Uplink instance is the version leader for
	 * the given responsibility, and if so, claims it so no other instance can.
	 *
	 * @since 3.0.0
	 *
	 * @param string $key A short, unique identifier for the responsibility
	 *                    (e.g. 'admin_page', 'rest_routes').
	 *
	 * @return bool True if this instance is the leader.
	 */
	public static function is_leader( string $key ): bool {
		$action  = 'stellarwp/uplink/leader/' . $key;
		$highest = apply_filters( 'stellarwp/uplink/highest_version', '0.0.0' );

		if ( version_compare( Uplink::VERSION, $highest, '<' ) ) {
			return false;
		}

		if ( did_action( $action ) ) {
			return false;
		}

		do_action( $action );

		return true;
	}
}
