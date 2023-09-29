<?php

namespace StellarWP\Uplink;

use RuntimeException;
use StellarWP\ContainerContract\ContainerInterface;

class Uplink {

	/**
	 * Initializes the service provider.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function init() {
		if ( ! Config::has_container() ) {
			throw new RuntimeException( 'You must call StellarWP\Uplink\Config::set_container() before calling StellarWP\Telemetry::init().' );
		}

		$container = Config::get_container();

		$container->bind( ContainerInterface::class, $container );
		$container->singleton( API\Client::class, API\Client::class );
		$container->singleton( Resources\Collection::class, Resources\Collection::class );
		$container->singleton( Site\Data::class, Site\Data::class );
		$container->singleton( Notice\Provider::class, Notice\Provider::class );
		$container->singleton( Admin\Provider::class, Admin\Provider::class );
		$container->singleton( View\Provider::class, View\Provider::class );
		$container->singleton( Auth\Provider::class, Auth\Provider::class );
		$container->singleton( Rest\Provider::class, Rest\Provider::class );

		if ( static::is_enabled() ) {
			$container->get( Notice\Provider::class )->register();
			$container->get( Admin\Provider::class )->register();

			if ( $container->has( Config::TOKEN_OPTION_NAME ) ) {
				$container->get( Auth\Provider::class )->register();
				$container->get( Rest\Provider::class )->register();
			}

			$container->get( View\Provider::class )->register();
		}

		require_once __DIR__ . '/functions.php';
	}

	/**
	 * Returns whether or not licensing validation is disabled.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function is_disabled() : bool {
		$is_pue_disabled       = defined( 'TRIBE_DISABLE_PUE' ) && TRIBE_DISABLE_PUE;
		$is_licensing_disabled = defined( 'STELLARWP_LICENSING_DISABLED' ) && STELLARWP_LICENSING_DISABLED;

		return $is_pue_disabled || $is_licensing_disabled;
	}

	/**
	 * Returns whether or not licensing validation is enabled.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function is_enabled() : bool {
		return ! static::is_disabled();
	}
}
