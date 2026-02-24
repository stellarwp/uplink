<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features;

use StellarWP\ContainerContract\ContainerInterface;
use StellarWP\Uplink\Contracts\Abstract_Provider;
use StellarWP\Uplink\Features\API\Client;
use StellarWP\Uplink\Features\REST\Toggle_Controller;
use StellarWP\Uplink\Features\Strategy\Resolver;

/**
 * Registers the Features subsystem in the DI container and hooks.
 *
 * @since TBD
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

		$this->container->singleton( Collection::class, Collection::class );

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

		$this->register_default_strategies();
		$this->register_hooks();
	}

	/**
	 * Register the default feature type strategies.
	 *
	 * Strategy implementations are not yet created, so this is a
	 * placeholder for when Zip_Strategy and Built_In_Strategy are added.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	private function register_default_strategies(): void {
		// TODO: Register default strategies once implemented.
		// $resolver = $this->container->get( Resolver::class );
		// $resolver->register( 'zip', Zip_Strategy::class );
		// $resolver->register( 'built_in', Built_In_Strategy::class );
	}

	/**
	 * Register WordPress hooks for the Features subsystem.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	private function register_hooks(): void {
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
	}

	/**
	 * Register REST API routes.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		$this->container->get( Toggle_Controller::class )->register_routes();
	}
}
