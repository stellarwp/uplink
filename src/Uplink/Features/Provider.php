<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features;

use StellarWP\ContainerContract\ContainerInterface;
use StellarWP\Uplink\Contracts\Abstract_Provider;
use StellarWP\Uplink\Features\API\Client;
use StellarWP\Uplink\Features\Strategy\Resolver;
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

		$this->container->singleton(
			Resolver::class,
			static function ( ContainerInterface $c ) {
				$container = $c->get( ContainerInterface::class );

				return new Resolver( $container );
			}
		);

		$this->container->singleton( Feature_Collection::class, Feature_Collection::class );

		$this->container->singleton(
			Manager::class,
			static function ( ContainerInterface $c ) {
				$client   = $c->get( Client::class );
				$resolver = $c->get( Resolver::class );

				return new Manager( $client, $resolver );
			}
		);

		$this->register_default_types();
		$this->register_default_strategies();
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
		// phpcs:disable Squiz.PHP.CommentedOutCode.Found, Squiz.Commenting.InlineComment.InvalidEndChar -- Placeholder for future strategy registration.
		// TODO: Register default strategies once implemented.
		// $resolver = $this->container->get( Resolver::class );
		// $resolver->register( 'zip', Zip_Strategy::class );
		// $resolver->register( 'built_in', Built_In_Strategy::class );
		// phpcs:enable Squiz.PHP.CommentedOutCode.Found, Squiz.Commenting.InlineComment.InvalidEndChar
	}
}
