<?php declare( strict_types=1 );

namespace StellarWP\Uplink\API\Functions;

use StellarWP\Uplink\Config;
use StellarWP\Uplink\Features\Manager;
use StellarWP\Uplink\Licensing\Repositories\License_Repository;
use Throwable;
use WP_Error;

/**
 * Registers this Uplink instance's callbacks into the global function registry.
 *
 * Each vendor-prefixed Uplink instance calls register() during init, storing
 * version-keyed closures via _stellarwp_uplink_global_function_registry(). The closures are defined
 * here (inside the namespaced file) so Strauss-prefixed class references
 * resolve correctly for this specific instance.
 *
 * @since 3.0.0
 */
class Global_Function_Registry {

	/**
	 * Registers this instance's callbacks into the global function registry.
	 *
	 * @since 3.0.0
	 *
	 * @param string $version The version of this Uplink instance.
	 *
	 * @return void
	 */
	public static function register( string $version ): void {
		\_stellarwp_uplink_global_function_registry(
			'stellarwp_uplink_has_unified_license_key',
			$version,
			static function (): bool {
				try {
					return Config::get_container()->get( License_Repository::class )->key_exists();
				} catch ( Throwable $e ) {
					self::debug_log( $e, 'Error checking unified license key existence' );

					return false;
				}
			}
		);

		\_stellarwp_uplink_global_function_registry(
			'stellarwp_uplink_get_unified_license_key',
			$version,
			static function (): ?string {
				try {
					return Config::get_container()->get( License_Repository::class )->get_key();
				} catch ( Throwable $e ) {
					self::debug_log( $e, 'Error getting unified license key' );

					return null;
				}
			}
		);

		\_stellarwp_uplink_global_function_registry(
			'stellarwp_uplink_is_product_license_active',
			$version,
			static function ( string $product ): bool {
				try {
					return Config::get_container()->get( License_Repository::class )->is_product_valid( $product );
				} catch ( Throwable $e ) {
					self::debug_log( $e, 'Error checking product license' );

					return false;
				}
			}
		);

		\_stellarwp_uplink_global_function_registry(
			'stellarwp_uplink_is_feature_enabled',
			$version,
			static function ( string $slug ) {
				try {
					$result = Config::get_container()->get( Manager::class )->is_enabled( $slug );

					if ( is_wp_error( $result ) ) {
						self::debug_log_wp_error( $result, 'Error checking feature enabled state' );

						return false;
					}

					return $result;
				} catch ( Throwable $e ) {
					self::debug_log( $e, 'Error checking feature enabled state' );

					return false;
				}
			}
		);

		\_stellarwp_uplink_global_function_registry(
			'stellarwp_uplink_is_feature_available',
			$version,
			static function ( string $slug ) {
				try {
					$result = Config::get_container()->get( Manager::class )->is_available( $slug );

					if ( is_wp_error( $result ) ) {
						self::debug_log_wp_error( $result, 'Error checking feature availability' );

						return false;
					}

					return $result;
				} catch ( Throwable $e ) {
					self::debug_log( $e, 'Error checking feature availability' );

					return false;
				}
			}
		);
	}

	/**
	 * Logs a Throwable message and trace when WP_DEBUG is enabled.
	 *
	 * @since 3.0.0
	 *
	 * @param Throwable $e The Throwable to log.
	 * @param string    $context The context of the log.
	 *
	 * @return void
	 */
	private static function debug_log( Throwable $e, string $context ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentionally logging.
			error_log( "{$context}: {$e->getMessage()} {$e->getFile()}:{$e->getLine()} {$e->getTraceAsString()}" );
		}
	}

	/**
	 * Logs a WP_Error message and trace when WP_DEBUG is enabled.
	 *
	 * @param WP_Error $error The WP_Error to log.
	 * @param string   $context The context of the log.
	 * @return void
	 */
	private static function debug_log_wp_error( WP_Error $error, string $context ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentionally logging.
			error_log( "{$context}: [{$error->get_error_code()}] {$error->get_error_message()}" );
		}
	}
}
