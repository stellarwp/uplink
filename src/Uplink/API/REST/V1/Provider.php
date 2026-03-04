<?php declare( strict_types=1 );

namespace StellarWP\Uplink\API\REST\V1;

use StellarWP\ContainerContract\ContainerInterface;
use StellarWP\Uplink\Catalog\Catalog_Repository;
use StellarWP\Uplink\Contracts\Abstract_Provider;
use StellarWP\Uplink\Features\Manager;
use StellarWP\Uplink\Licensing\License_Manager;
use StellarWP\Uplink\Utils\Version;

/**
 * Registers all v1 WP REST API controllers and hooks routes.
 *
 * @since 3.0.0
 */
final class Provider extends Abstract_Provider {

	/**
	 * @inheritDoc
	 */
	public function register(): void {
		$this->container->singleton(
			Feature_Controller::class,
			static function ( ContainerInterface $c ) {
				return new Feature_Controller( $c->get( Manager::class ) );
			}
		);

		$this->container->singleton(
			License_Controller::class,
			static function ( ContainerInterface $c ) {
				return new License_Controller( $c->get( License_Manager::class ) );
			}
		);

		$this->container->singleton(
			Catalog_Controller::class,
			static function ( ContainerInterface $c ) {
				return new Catalog_Controller( $c->get( Catalog_Repository::class ) );
			}
		);

		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
	}

	/**
	 * Registers all v1 REST API routes.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		if ( Version::should_handle( 'register_rest_routes_v1' ) ) {
			$this->container->get( Feature_Controller::class )->register_routes();
			$this->container->get( License_Controller::class )->register_routes();
			$this->container->get( Catalog_Controller::class )->register_routes();
		}
	}
}
