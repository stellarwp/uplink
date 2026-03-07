<?php declare( strict_types=1 );

namespace StellarWP\Uplink\API\Functions;

use StellarWP\ContainerContract\ContainerInterface;
use StellarWP\Uplink\Features\Error_Code;
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
	 * @param ContainerInterface $container The container for this Uplink instance.
	 * @param string             $version   The version of this Uplink instance.
	 *
	 * @return void
	 */
	public static function register( ContainerInterface $container, string $version ): void {
		// @phpstan-ignore function.internal
		\_stellarwp_uplink_global_function_registry(
			'stellarwp_uplink_has_unified_license_key',
			$version,
			static function () use ( $container ): bool {
				try {
					return $container->get( License_Repository::class )->key_exists();
				} catch ( Throwable $e ) {
					self::debug_log( $e, 'Error checking unified license key existence' );

					return false;
				}
			}
		);

		// @phpstan-ignore function.internal
		\_stellarwp_uplink_global_function_registry(
			'stellarwp_uplink_get_unified_license_key',
			$version,
			static function () use ( $container ): ?string {
				try {
					return $container->get( License_Repository::class )->get_key();
				} catch ( Throwable $e ) {
					self::debug_log( $e, 'Error getting unified license key' );

					return null;
				}
			}
		);

		// @phpstan-ignore function.internal
		\_stellarwp_uplink_global_function_registry(
			'stellarwp_uplink_is_product_license_active',
			$version,
			static function ( string $product ) use ( $container ): bool {
				try {
					return $container->get( License_Repository::class )->is_product_valid( $product );
				} catch ( Throwable $e ) {
					self::debug_log( $e, 'Error checking product license' );

					return false;
				}
			}
		);

		// @phpstan-ignore function.internal
		\_stellarwp_uplink_global_function_registry(
			'stellarwp_uplink_is_feature_enabled',
			$version,
			static function ( string $slug ) use ( $container ) {
				try {
					return $container->get( Manager::class )->is_enabled( $slug );
				} catch ( Throwable $e ) {
					self::debug_log( $e, 'Error checking feature enabled state' );

					$message = $e instanceof \Exception ? $e->getMessage() : 'An unexpected error occurred.';

					return new WP_Error( Error_Code::FEATURE_CHECK_FAILED, $message );
				}
			}
		);

		// @phpstan-ignore function.internal
		\_stellarwp_uplink_global_function_registry(
			'stellarwp_uplink_is_feature_available',
			$version,
			static function ( string $slug ) use ( $container ) {
				try {
					return $container->get( Manager::class )->is_available( $slug );
				} catch ( Throwable $e ) {
					self::debug_log( $e, 'Error checking feature availability' );

					$message = $e instanceof \Exception ? $e->getMessage() : 'An unexpected error occurred.';

					return new WP_Error( Error_Code::FEATURE_CHECK_FAILED, $message );
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
}
