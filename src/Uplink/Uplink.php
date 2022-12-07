<?php

namespace StellarWP\Uplink;

use StellarWP\ContainerContract\ContainerInterface;
use StellarWP\Uplink\Admin\Provider;

class Uplink {

	/**
	 * The container that should be used for loading library resources.
	 *
	 * @since 1.0.0
	 *
	 * @var ContainerInterface
	 */
	private ContainerInterface $container;

	/**
	 * Initializes the service provider.
	 *
	 * @since 1.0.0
	 */
	public function init() : void {
		if ( ! Config::has_container() ) {
			throw new \RuntimeException( 'You must call StellarWP\Uplink\Config::set_container() before calling StellarWP\Telemetry::init().' );
		}

		$this->register();
	}

	/**
	 * The current instance of the library.
	 *
	 * @since 1.0.0
	 *
	 * @var self
	 */
	private static self $instance;

	/**
	 * Returns the current instance or creates one to return.
	 *
	 * @since 1.0.0
	 *
	 * @return self
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Gets the container.
	 *
	 * @since 1.0.0
	 *
	 * @return ContainerInterface
	 */
	public function container() {
		return $this->container;
	}

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 1.0.0
	 */
	public function register() {
		$container 		 = Config::get_container();
		$this->container = $container;

		$this->container->singleton( static::class, $this );
		$this->container->singleton( API\Client::class, API\Client::class );
		$this->container->singleton( Resources\Collection::class, Resources\Collection::class );
		$this->container->singleton( Site\Data::class, Site\Data::class );

		(new Provider( $this->container ))->register();

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
