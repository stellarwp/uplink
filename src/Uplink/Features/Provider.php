<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features;

use StellarWP\ContainerContract\ContainerInterface;
use StellarWP\Uplink\Contracts\Abstract_Provider;
use StellarWP\Uplink\Features\API\Client;
use StellarWP\Uplink\Features\REST\Toggle_Controller;
use StellarWP\Uplink\Features\Strategy\Built_In_Strategy;
use StellarWP\Uplink\Features\Strategy\Resolver;
use StellarWP\Uplink\Features\Strategy\Zip_Strategy;
use StellarWP\Uplink\Features\Types\Built_In;
use StellarWP\Uplink\Features\Types\Zip;

/**
 * Registers the Features subsystem in the DI container and hooks.
 *
 * @since 3.0.0
 */
class Provider extends Abstract_Provider {

	/**
	 * @inheritDoc
	 */
	public function register(): void {
		$this->container->singleton( Client::class, Client::class );

		$this->container->singleton( Resolver::class, static function ( $c ) {
			return new Resolver( $c->get( ContainerInterface::class ) );
		} );

		$this->container->singleton( Feature_Collection::class, Feature_Collection::class );

		$this->container->singleton( Manager::class, static function ( $c ) {
			return new Manager(
				$c->get( Client::class ),
				$c->get( Resolver::class )
			);
		} );

		$this->container->singleton( Toggle_Controller::class, static function ( $c ) {
			return new Toggle_Controller(
				$c->get( Manager::class )
			);
		} );

		$this->register_default_types();
		$this->register_default_strategies();
		$this->register_hooks();
	}

	/**
	 * Registers the default feature type to class mappings.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	private function register_default_types(): void {
		$client = $this->container->get( Client::class );
		$client->register_type( 'zip', Zip::class );
		$client->register_type( 'built_in', Built_In::class );
	}

	/**
	 * Registers the default feature type strategies.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	private function register_default_strategies(): void {
		$this->container->singleton( Zip_Strategy::class, Zip_Strategy::class );
		$this->container->singleton( Built_In_Strategy::class, Built_In_Strategy::class );

		$resolver = $this->container->get( Resolver::class );
		$resolver->register( 'zip', Zip_Strategy::class );
		$resolver->register( 'built_in', Built_In_Strategy::class );
	}

	/**
	 * Registers WordPress hooks for the Features subsystem.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	private function register_hooks(): void {
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
	}

	/**
	 * Registers REST API routes.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		$this->container->get( Toggle_Controller::class )->register_routes();
	}
}
