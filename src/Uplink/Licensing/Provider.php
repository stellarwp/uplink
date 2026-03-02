<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Licensing;

use StellarWP\ContainerContract\ContainerInterface;
use StellarWP\Uplink\Contracts\Abstract_Provider;
use StellarWP\Uplink\Licensing\Contracts\Licensing_Client;

/**
 * Registers the Licensing subsystem in the DI container.
 *
 * @since 3.0.0
 */
class Provider extends Abstract_Provider {

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

		$this->container->singleton(
			Product_Repository::class,
			static function ( ContainerInterface $c ) {
				return new Product_Repository( $c->get( Licensing_Client::class ) );
			}
		);
	}
}
