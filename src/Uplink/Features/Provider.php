<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use StellarWP\ContainerContract\ContainerInterface;
use StellarWP\Uplink\Catalog\Catalog_Repository;
use StellarWP\Uplink\Config;
use StellarWP\Uplink\Contracts\Abstract_Provider;
use StellarWP\Uplink\Features\Dependency\Clients\Dependency_Client;
use StellarWP\Uplink\Features\Dependency\Clients\Http_Client as Dependency_Http_Client;
use StellarWP\Uplink\Features\Dependency\Feature_Dependency_Repository;
use StellarWP\Uplink\Features\Strategy\Strategy_Factory;
use StellarWP\Uplink\Features\Types\Feature;
use StellarWP\Uplink\Features\Types\Flag;
use StellarWP\Uplink\Features\Types\Plugin;
use StellarWP\Uplink\Features\Types\Theme;
use StellarWP\Uplink\Licensing\License_Manager;
use StellarWP\Uplink\Site\Data;

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
		$this->container->singleton( Strategy_Factory::class, Strategy_Factory::class );

		$this->container->singleton(
			Dependency_Client::class,
			function () {
				return new Dependency_Http_Client(
					$this->container->get( ClientInterface::class ),
					$this->container->get( RequestFactoryInterface::class ),
					Config::get_api_base_url()
				);
			}
		);

		$this->container->singleton(
			Feature_Dependency_Repository::class,
			static function ( ContainerInterface $c ) {
				return new Feature_Dependency_Repository(
					$c->get( Dependency_Client::class )
				);
			}
		);

		$this->container->singleton(
			Resolve_Feature_Collection::class,
			function ( ContainerInterface $c ) {
				$resolver = new Resolve_Feature_Collection(
					$c->get( Catalog_Repository::class ),
					$c->get( License_Manager::class ),
					$c->get( Data::class ),
					$c->get( Feature_Dependency_Repository::class )
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
					$c->get( Strategy_Factory::class )
				);
			}
		);

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
		$resolver->register_type( Feature::TYPE_PLUGIN, Plugin::class );
		$resolver->register_type( Feature::TYPE_FLAG, Flag::class );
		$resolver->register_type( Feature::TYPE_THEME, Theme::class );
	}
}
