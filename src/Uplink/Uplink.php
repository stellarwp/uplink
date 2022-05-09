<?php

namespace StellarWP\Uplink;

class Uplink extends \tad_DI52_ServiceProvider {
	/**
	 * Initializes the service provider.
	 *
	 * @since 1.0.0
	 */
	public static function init() : void {
		Container::init()->register( static::class );
	}

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 1.0.0
	 */
	public function register() {
		$this->container->singleton( static::class, $this );
		$this->container->singleton( API\Client::class, API\Client::class );
		$this->container->singleton( Resources\Collection::class, Resources\Collection::class );
		$this->container->singleton( Site\Data::class, Site\Data::class );

		$this->container->register( Admin\Provider::class );

		$this->register_hooks();
	}

	/**
	 * Registers all hooks.
	 *
	 * @since 1.0.0
	 */
	private function register_hooks() : void {
	}
}
