<?php declare( strict_types=1 );

namespace StellarWP\Uplink\API\Functions;

use StellarWP\ContainerContract\ContainerInterface;
use StellarWP\Uplink\Features\Error_Code;
use StellarWP\Uplink\Features\Manager;
use StellarWP\Uplink\Licensing\License_Manager;
use StellarWP\Uplink\Licensing\Repositories\License_Repository;
use Throwable;
use WP_Error;

/**
 * Registers this Uplink instance's callbacks into the global function registry.
 *
 * Each vendor-prefixed Uplink instance calls register() during init, storing
 * version-keyed closures via uplink_fn_registry(). The closures are defined
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
		\uplink_fn_registry(
			'has_unified_license_key',
			$version,
			static function () use ( $container ): bool {
				try {
					return $container->get( License_Manager::class )->key_exists();
				} catch ( Throwable $e ) {
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentionally logging.
						error_log( "Error checking unified license key existence: {$e->getMessage()} {$e->getFile()}:{$e->getLine()} {$e->getTraceAsString()}" );
					}
					return false;
				}
			}
		);

		// @phpstan-ignore function.internal
		\uplink_fn_registry(
			'is_product_license_active',
			$version,
			static function ( string $product ) use ( $container ): bool {
				try {
					return $container->get( License_Repository::class )->is_product_valid( $product );
				} catch ( Throwable $e ) {
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentionally logging.
						error_log( "Error checking product license: {$e->getMessage()} {$e->getFile()}:{$e->getLine()} {$e->getTraceAsString()}" );
					}
					return false;
				}
			}
		);

		// @phpstan-ignore function.internal
		\uplink_fn_registry(
			'is_feature_enabled',
			$version,
			static function ( string $slug ) use ( $container ) {
				try {
					return $container->get( Manager::class )->is_enabled( $slug );
				} catch ( Throwable $e ) {
					if ( $e instanceof \Exception && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentionally logging.
						error_log( "Error checking feature enabled state: {$e->getMessage()} {$e->getFile()}:{$e->getLine()} {$e->getTraceAsString()}" );
					}
					$message = $e instanceof \Exception ? $e->getMessage() : 'An unexpected error occurred.';
					return new WP_Error( Error_Code::FEATURE_CHECK_FAILED, $message );
				}
			}
		);

		// @phpstan-ignore function.internal
		\uplink_fn_registry(
			'is_feature_available',
			$version,
			static function ( string $slug ) use ( $container ) {
				try {
					return $container->get( Manager::class )->is_available( $slug );
				} catch ( Throwable $e ) {
					if ( $e instanceof \Exception && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentionally logging.
						error_log( "Error checking feature availability: {$e->getMessage()} {$e->getFile()}:{$e->getLine()} {$e->getTraceAsString()}" );
					}
					$message = $e instanceof \Exception ? $e->getMessage() : 'An unexpected error occurred.';
					return new WP_Error( Error_Code::FEATURE_CHECK_FAILED, $message );
				}
			}
		);
	}
}
