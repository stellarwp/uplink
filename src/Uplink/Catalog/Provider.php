<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Catalog;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use StellarWP\Uplink\Catalog\Clients\Catalog_Client;
use StellarWP\Uplink\Catalog\Clients\Http_Client;
use StellarWP\Uplink\Config;
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
			function () {
				return new Http_Client(
					$this->container->get( ClientInterface::class ),
					$this->container->get( RequestFactoryInterface::class ),
					Config::get_api_base_url()
				);
			}
		);

		$this->container->singleton( Catalog_Repository::class, Catalog_Repository::class );

		add_action(
			'stellarwp/uplink/unified_license_key_changed',
			static function () {
				delete_option( Catalog_Repository::CATALOG_STATE_OPTION_NAME );
			}
		);
	}
}
