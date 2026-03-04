<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Catalog;

use StellarWP\Uplink\Catalog\Contracts\Catalog_Client;
use StellarWP\Uplink\Contracts\Abstract_Provider;
use StellarWP\Uplink\Licensing\License_Manager;

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
		$container = $this->container;

		$this->container->singleton(
			Catalog_Client::class,
			static function () use ( $container ) {
				$catalog_dir = trailingslashit( dirname( __DIR__, 3 ) ) . 'tests/_data/catalog';

				$license_manager = $container->get( License_Manager::class );
				$key             = $license_manager->get();

				if ( $key !== null && file_exists( trailingslashit( $catalog_dir ) . $key . '.json' ) ) {
					return new Fixture_Client( trailingslashit( $catalog_dir ) . $key . '.json' );
				}

				return new Fixture_Client( trailingslashit( $catalog_dir ) . 'default.json' );
			}
		);

		$this->container->singleton( Catalog_Repository::class, Catalog_Repository::class );

		add_action(
			'stellarwp/uplink/unified_license_key_changed',
			static function () {
				delete_transient( Catalog_Repository::TRANSIENT_KEY );
			}
		);
	}
}
