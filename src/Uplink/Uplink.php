<?php

namespace StellarWP\Uplink;

use StellarWP\ContainerContract\ContainerInterface;
use StellarWP\Uplink\Admin\Provider;
use StellarWP\Uplink\API\Client;
use StellarWP\Uplink\Resources\Collection;
use StellarWP\Uplink\Site\Data;

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
		$container = Config::get_container();

		$container->bind( static::class, $this );
		$container->bind( Client::class, static function () use ( $container ) {
			return new Client( $container );
		} );
		$container->bind( Collection::class, static function () {
			return new Collection();
		} );
		$container->bind( Data::class, static function () use ( $container ) {
			return new Data( $container );
		} );

		$this->container = $container;

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
