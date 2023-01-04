<?php

namespace StellarWP\Uplink;

class Uplink {

	/**
	 * Initializes the service provider.
	 *
	 * @since 1.0.0
	 */
	public static function init() : void {
		if ( ! Config::has_container() ) {
			throw new \RuntimeException( 'You must call StellarWP\Uplink\Config::set_container() before calling StellarWP\Telemetry::init().' );
		}

		$container = Config::get_container();

		$container->singleton( API\Client::class, API\Client::class );
		$container->singleton( Resources\Collection::class, Resources\Collection::class );
		$container->singleton( Site\Data::class, Site\Data::class );
		$container->singleton( Admin\Provider::class, Admin\Provider::class );
		$container->get( Admin\Provider::class )->register();
	}
}
