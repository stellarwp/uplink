<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features;

use StellarWP\ContainerContract\ContainerInterface;
use StellarWP\Uplink\Catalog\Catalog_Repository;
use StellarWP\Uplink\Contracts\Abstract_Provider;
use StellarWP\Uplink\Features\REST\Feature_Controller;
use StellarWP\Uplink\Features\Strategy\Resolver;
use StellarWP\Uplink\Licensing\Product_Repository;
use StellarWP\Uplink\Site\Data;
use StellarWP\Uplink\Utils\Version;

/**
 * Registers the Features subsystem in the DI container and hooks.
 *
 * @since 3.0.0
 */
class Provider extends Abstract_Provider {

	/**
	 * Registers singletons and hooks for the Features subsystem.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function register(): void {
		$this->container->singleton(
			Resolver::class,
			static function ( ContainerInterface $c ) {
				$container = $c->get( ContainerInterface::class );

				return new Resolver( $container );
			}
		);

		$this->container->singleton(
			Feature_Repository::class,
			static function ( ContainerInterface $c ) {
				return new Feature_Repository(
					$c->get( Catalog_Repository::class ),
					$c->get( Product_Repository::class )
				);
			}
		);

		$this->container->singleton( Feature_Collection::class, Feature_Collection::class );

		$this->container->singleton(
			Manager::class,
			static function ( ContainerInterface $c ) {
				return new Manager(
					$c->get( Feature_Repository::class ),
					$c->get( Resolver::class ),
					'', // TODO: Unified license key lookup.
					$c->get( Data::class )->get_domain()
				);
			}
		);

		$this->container->singleton(
			Feature_Controller::class,
			static function ( ContainerInterface $c ) {
				$manager = $c->get( Manager::class );

				return new Feature_Controller( $manager );
			}
		);

		$this->register_default_strategies();
		$this->register_hooks();
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
		// phpcs:disable Squiz.PHP.CommentedOutCode.Found, Squiz.Commenting.InlineComment.InvalidEndChar -- Placeholder for future strategy registration.
		// TODO: Register default strategies once implemented.
		// $resolver = $this->container->get( Resolver::class );
		// $resolver->register( 'zip', Zip_Strategy::class );
		// $resolver->register( 'built_in', Built_In_Strategy::class );
		// phpcs:enable Squiz.PHP.CommentedOutCode.Found, Squiz.Commenting.InlineComment.InvalidEndChar
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
