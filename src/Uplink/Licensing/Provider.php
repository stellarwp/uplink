<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Licensing;

use StellarWP\Uplink\Contracts\Abstract_Provider;
use StellarWP\Uplink\Licensing\Contracts\Licensing_Client;
use StellarWP\Uplink\Licensing\Registry\Product_Registry;
use StellarWP\Uplink\Licensing\Repositories\License_Repository;
use StellarWP\Uplink\Licensing\REST\License_Controller;
use StellarWP\Uplink\Utils\Version;

/**
 * Registers the Licensing subsystem in the DI container.
 *
 * @since 3.0.0
 */
final class Provider extends Abstract_Provider {

	/**
	 * @inheritDoc
	 */
	public function register(): void {
		$this->container->singleton(
			Licensing_Client::class,
			static function () {
				return new Fixture_Client(
					dirname( __DIR__, 3 ) . '/tests/_data/licensing'
				);
			}
		);

		$this->container->singleton( Product_Repository::class, Product_Repository::class );
		$this->container->singleton( License_Repository::class, License_Repository::class );
		$this->container->singleton( Product_Registry::class, Product_Registry::class );
		$this->container->singleton( License_Manager::class, License_Manager::class );
		$this->container->singleton( License_Controller::class, License_Controller::class );

		$this->register_hooks();
	}

	/**
	 * Registers WordPress hooks for the Licensing subsystem.
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
		if ( ! Version::should_handle( 'licensing_rest_routes' ) ) {
			return;
		}

		$this->container->get( License_Controller::class )->register_routes();
	}
}
