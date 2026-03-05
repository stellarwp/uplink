<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features;

use StellarWP\ContainerContract\ContainerInterface;
use StellarWP\Uplink\Catalog\Catalog_Repository;
use StellarWP\Uplink\Contracts\Abstract_Provider;
use StellarWP\Uplink\Features\Strategy\Flag_Strategy;
use StellarWP\Uplink\Features\Strategy\Resolver;
use StellarWP\Uplink\Features\Strategy\Plugin_Strategy;
use StellarWP\Uplink\Features\Types\Flag;
use StellarWP\Uplink\Features\Types\Plugin;
use StellarWP\Uplink\Licensing\License_Manager;
use StellarWP\Uplink\Site\Data;
use StellarWP\Uplink\Utils\Cast;
use WP_Error;

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
			Resolve_Feature_Collection::class,
			function ( ContainerInterface $c ) {
				$resolver = new Resolve_Feature_Collection(
					$c->get( Catalog_Repository::class ),
					$c->get( License_Manager::class )
				);

				$this->register_default_types( $resolver );

				return $resolver;
			}
		);

		$this->container->singleton(
			Feature_Repository::class,
			static function ( ContainerInterface $c ) {
				return new Feature_Repository(
					$c->get( Resolve_Feature_Collection::class )
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
					$c->get( License_Manager::class )->get_key() ?? '',
					$c->get( Data::class )->get_domain()
				);
			}
		);

		$this->register_default_strategies();
		$this->register_hooks();

		$this->container->singleton( Update\Provider::class, Update\Provider::class );
		$this->container->get( Update\Provider::class )->register();
	}

	/**
	 * Registers the default feature type to class mappings.
	 *
	 * @since 3.0.0
	 *
	 * @param Resolve_Feature_Collection $resolver The feature collection resolver.
	 *
	 * @return void
	 */
	private function register_default_types( Resolve_Feature_Collection $resolver ): void {
		$resolver->register_type( 'plugin', Plugin::class );
		$resolver->register_type( 'flag', Flag::class );
		$resolver->register_type( 'theme', Plugin::class ); // TODO: Will be replaced with Theme type.
	}

	/**
	 * Registers the default feature type strategies.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	private function register_default_strategies(): void {
		$this->container->singleton( Plugin_Strategy::class, Plugin_Strategy::class );
		$this->container->singleton( Flag_Strategy::class, Flag_Strategy::class );

		$resolver = $this->container->get( Resolver::class );
		$resolver->register( 'plugin', Plugin_Strategy::class );
		$resolver->register( 'flag', Flag_Strategy::class );
		$resolver->register( 'theme', Plugin_Strategy::class ); // TODO: Will be replaced with Theme_Strategy.
	}

	/**
	 * Registers WordPress hooks for the Features subsystem.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	private function register_hooks(): void {
		add_action(
			'stellarwp/uplink/unified_license_key_changed',
			static function () {
				delete_transient( Feature_Repository::TRANSIENT_KEY );
			}
		);
	}
}
