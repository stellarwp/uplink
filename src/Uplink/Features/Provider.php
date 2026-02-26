<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features;

use StellarWP\ContainerContract\ContainerInterface;
use StellarWP\Uplink\Contracts\Abstract_Provider;
use StellarWP\Uplink\Features\API\Client;
use StellarWP\Uplink\Features\REST\Feature_Controller;
use StellarWP\Uplink\Features\Strategy\Resolver;
use StellarWP\Uplink\Features\Types\Built_In;
use StellarWP\Uplink\Features\Types\Zip;
use StellarWP\Uplink\Utils\Version;

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

		$this->container->singleton( Resolver::class, static function ( ContainerInterface $c ) {
			$container = $c->get( ContainerInterface::class );

			return new Resolver( $container );
		} );

		$this->container->singleton( Feature_Collection::class, Feature_Collection::class );

		$this->container->singleton( Manager::class, static function ( ContainerInterface $c ) {
			$client = $c->get( Client::class );
			$resolver = $c->get( Resolver::class );

			return new Manager( $client, $resolver );
		} );

		$this->container->singleton( Feature_Controller::class, static function ( ContainerInterface $c ) {
			$manager = $c->get( Manager::class );

			return new Feature_Controller( $manager );
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
	 * Strategy implementations are not yet created, so this is a
	 * placeholder for when Zip_Strategy and Built_In_Strategy are added.
	 *
	 * @since 3.0.0
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
		if ( ! Version::should_handle( 'features_rest_routes' ) ) {
			return;
		}

		$this->container->get( Feature_Controller::class )->register_routes();
	}
}
