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
	 * Default base URL for the catalog API.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const DEFAULT_BASE_URL = 'https://licensing.stellarwp.com';

	/**
	 * @inheritDoc
	 */
	public function register(): void {
		$container = $this->container;

		$this->container->singleton(
			Catalog_Client::class,
			static function () use ( $container ) {
				/**
				 * Filters the base URL for the catalog API.
				 *
				 * @since 3.0.0
				 *
				 * @param string $base_url The base URL (no trailing slash).
				 */
				$base_url = (string) apply_filters(
					'stellarwp/uplink/catalog/base_url',
					self::DEFAULT_BASE_URL
				);

				/** @var License_Manager $license_manager */
				$license_manager = $container->get( License_Manager::class );
				$key             = $license_manager->get_key();

				return new Http_Client( $base_url, $key );
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
