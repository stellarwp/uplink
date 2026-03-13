<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Utils;

use StellarWP\Uplink\Config;
use StellarWP\Uplink\Uplink;

/**
 * Cross-instance version leadership utility.
 *
 * When multiple vendor-prefixed copies of Uplink are active, only the
 * highest version should own shared responsibilities (admin page, REST
 * routes, etc.). This class centralizes that check using the global
 * _stellarwp_uplink_instance_registry() function as the cross-copy registry.
 *
 * @since 3.0.0
 */
class Version {

	/**
	 * Whether this instance has claimed at least one leadership responsibility.
	 *
	 * Set to true the first time should_handle() succeeds. Stored as a static
	 * on this (Strauss-prefixed) class, so each vendor copy tracks its own state.
	 *
	 * @since 3.0.0
	 *
	 * @var bool
	 */
	private static $claimed_leadership = false;

	/**
	 * Determines whether this Uplink instance is the highest active version.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public static function is_highest(): bool {
		// @phpstan-ignore function.internal
		return self::is_highest_among( array_keys( _stellarwp_uplink_instance_registry() ) );
	}

	/**
	 * Determines whether this Uplink instance is the highest among the given versions.
	 *
	 * @since 3.0.0
	 *
	 * @param string[] $versions All registered version strings.
	 *
	 * @return bool
	 */
	public static function is_highest_among( array $versions ): bool {
		$highest = array_reduce(
			$versions,
			static function ( string $carry, string $v ): string {
				return version_compare( $v, $carry, '>' ) ? $v : $carry;
			},
			Uplink::VERSION
		);

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

		self::$claimed_leadership = true;

		return true;
	}

	/**
	 * Returns whether this instance has claimed at least one leadership responsibility.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public static function is_leader(): bool {
		return self::$claimed_leadership;
	}

	/**
	 * Prints the debug info to the admin footer.
	 *
	 * TODO: We can remove this before launch.
	 *
	 * @return void
	 */
	public static function debug_info(): void {
		if (defined('WP_DEBUG') && WP_DEBUG) {
			$container_parts = explode('\\', get_class(Config::get_container()));
			$prefix          = Config::get_hook_prefix() ?: $container_parts[0];
			$version         = Uplink::VERSION;

			add_action(
				'admin_footer',
				static function () use ($prefix, $version) {
					if (! Version::is_leader()) {
						return;
					}
					$data = [
						'stellarwp/uplink' => [
							'leader' => $prefix,
							'version' => $version,
						],
					];

					echo "<script>console.log(" . json_encode($data) . ");</script>";
				}
			);
		}
	}
}
