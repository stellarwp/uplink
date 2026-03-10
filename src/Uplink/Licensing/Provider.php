<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Licensing;

use StellarWP\Uplink\Contracts\Abstract_Provider;
use StellarWP\Uplink\Licensing\Contracts\Licensing_Client;
use StellarWP\Uplink\Licensing\Registry\Product_Registry;
use StellarWP\Uplink\Licensing\Repositories\License_Repository;

/**
 * Registers the Licensing subsystem in the DI container.
 *
 * @since 3.0.0
 */
final class Provider extends Abstract_Provider {

	/**
	 * Default base URL for the licensing API.
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
		$this->container->singleton(
			Licensing_Client::class,
			static function () {
				/**
				 * Filters the base URL for the licensing API.
				 *
				 * @since 3.0.0
				 *
				 * @param string $base_url The base URL (no trailing slash).
				 */
				$base_url = (string) apply_filters(
					'stellarwp/uplink/licensing/base_url',
					self::DEFAULT_BASE_URL
				);

				return new Http_Client( $base_url );
			}
		);

		$this->container->singleton( License_Repository::class, License_Repository::class );
		$this->container->singleton( Product_Registry::class, Product_Registry::class );
		$this->container->singleton( License_Manager::class, License_Manager::class );

		add_action(
			'stellarwp/uplink/unified_license_key_changed',
			function () {
				/** @var License_Repository $license_repository */
				$license_repository = $this->container->get( License_Repository::class );
				$license_repository->delete_products();
			}
		);
	}
}
