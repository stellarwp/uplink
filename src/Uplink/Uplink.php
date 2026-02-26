<?php declare( strict_types=1 );

namespace StellarWP\Uplink;

use RuntimeException;
use StellarWP\ContainerContract\ContainerInterface;

class Uplink {

	/**
	 * The Uplink library version.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const VERSION = '3.0.0';

	public const UPLINK_ADMIN_VIEWS_PATH = 'uplink.admin-views.path';
	public const UPLINK_ASSETS_URI       = 'uplink.assets.uri';

	/**
	 * Initializes the service provider.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function init(): void {
		if ( ! Config::has_container() ) {
			throw new RuntimeException(
				__( 'You must call StellarWP\Uplink\Config::set_container() before calling StellarWP\Uplink::init().', '%TEXTDOMAIN%' )
			);
		}

		$container = Config::get_container();

		$container->singleton( self::UPLINK_ADMIN_VIEWS_PATH, dirname( __DIR__ ) . '/admin-views' );
		$container->singleton( self::UPLINK_ASSETS_URI, dirname( plugin_dir_url( __FILE__ ) ) . '/assets' );
		$container->bind( ContainerInterface::class, $container );
		$container->singleton( Storage\Provider::class, Storage\Provider::class );
		$container->singleton( View\Provider::class, View\Provider::class );
		$container->singleton( API\Client::class, API\Client::class );
		$container->singleton( API\V3\Provider::class, API\V3\Provider::class );
		$container->singleton( Resources\Collection::class, Resources\Collection::class );
		$container->singleton( Site\Data::class, Site\Data::class );
		$container->singleton( Notice\Provider::class, Notice\Provider::class );
		$container->singleton( Admin\Provider::class, Admin\Provider::class );
		$container->singleton( Auth\Provider::class, Auth\Provider::class );

		if ( static::is_enabled() ) {
			$container->get( Storage\Provider::class )->register();
			$container->get( View\Provider::class )->register();
			$container->get( API\V3\Provider::class )->register();
			$container->get( Notice\Provider::class )->register();
			$container->get( Admin\Provider::class )->register();

			if ( $container->has( Config::TOKEN_OPTION_NAME ) ) {
				$container->get( Auth\Provider::class )->register();
			}

			static::register_cross_instance_hooks( $container );
		}

		require_once __DIR__ . '/functions.php';
	}

	/**
	 * Registers shared, non-prefixed WordPress hooks that enable cross-instance
	 * communication between vendor-prefixed copies of Uplink.
	 *
	 * Each handler checks its own Collection and only responds for resources
	 * it owns, allowing the unified UI to delegate operations to the correct instance.
	 *
	 * @since 3.0.0
	 *
	 * @param ContainerInterface $container The container for this Uplink instance.
	 *
	 * @return void
	 */
	protected static function register_cross_instance_hooks( ContainerInterface $container ): void {
		$get_resource = static function ( string $slug ) use ( $container ): ?Resources\Resource {
			return $container->get( Resources\Collection::class )->get( $slug );
		};

		add_filter(
			'stellarwp/uplink/highest_version',
			static function ( string $current_highest ) {
				if ( version_compare( self::VERSION, $current_highest, '>' ) ) {
					return self::VERSION;
				}
				return $current_highest;
			} 
		);

		add_filter(
			'stellarwp/uplink/validate_license',
			static function ( $result, string $slug, string $key ) use ( $get_resource ) {
				$resource = $get_resource( $slug );
				if ( ! $resource ) {
					return $result;
				}
				return $resource->validate_license( $key );
			},
			10,
			3 
		);

		add_filter(
			'stellarwp/uplink/set_license_key',
			static function ( $result, string $slug, string $key, string $type ) use ( $get_resource ) {
				$resource = $get_resource( $slug );
				if ( ! $resource ) {
					return $result;
				}
				$resource->set_license_key( $key, $type );
				$resource->validate_license( $key, $type === 'network' );
				return true;
			},
			10,
			4 
		);

		add_filter(
			'stellarwp/uplink/delete_license_key',
			static function ( $result, string $slug, string $type ) use ( $get_resource ) {
				$resource = $get_resource( $slug );
				if ( ! $resource ) {
					return $result;
				}
				return $resource->delete_license_key( $type );
			},
			10,
			3 
		);
	}

	/**
	 * Returns whether licensing validation is disabled.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function is_disabled(): bool {
		$is_pue_disabled       = defined( 'TRIBE_DISABLE_PUE' ) && TRIBE_DISABLE_PUE;
		$is_licensing_disabled = defined( 'STELLARWP_LICENSING_DISABLED' ) && STELLARWP_LICENSING_DISABLED;

		return $is_pue_disabled || $is_licensing_disabled;
	}

	/**
	 * Returns whether licensing validation is enabled.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function is_enabled(): bool {
		return ! static::is_disabled();
	}
}
