<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Catalog;

use StellarWP\Uplink\Catalog\Contracts\Catalog_Client;
use StellarWP\Uplink\Contracts\Abstract_Provider;

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
	}
}
