<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Catalog;

use StellarWP\Uplink\Catalog\Contracts\Catalog_Client;
use StellarWP\Uplink\Catalog\REST\Catalog_Controller;
use StellarWP\Uplink\Contracts\Abstract_Provider;
use StellarWP\Uplink\Utils\Version;

/**
 * Registers the Catalog subsystem in the DI container.
 *
 * @since 3.0.0
 */
final class Provider extends Abstract_Provider {

	/**
	 * @inheritDoc
	 */
	public function register(): void {
		$this->container->singleton(
			Catalog_Client::class,
			static function () {
				return new Fixture_Client(
					dirname( __DIR__, 3 ) . '/tests/_data/catalog.json'
				);
			}
		);

		$this->container->singleton( Catalog_Repository::class, Catalog_Repository::class );
		$this->container->singleton( Catalog_Controller::class, Catalog_Controller::class );

		$this->register_hooks();
	}

	/**
	 * Registers WordPress hooks for the Catalog subsystem.
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
		if ( ! Version::should_handle( 'catalog_rest_routes' ) ) {
			return;
		}

		$this->container->get( Catalog_Controller::class )->register_routes();
	}
}
