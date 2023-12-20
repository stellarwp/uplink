<?php declare( strict_types=1 );

namespace StellarWP\Uplink\License;

use RuntimeException;
use StellarWP\Uplink\Contracts\Abstract_Provider;
use StellarWP\Uplink\License\Manager\License_Handler;
use StellarWP\Uplink\License\Manager\Pipeline\Processors\Multisite_Domain_Mapping;
use StellarWP\Uplink\License\Manager\Pipeline\Processors\Multisite_Main_Site;
use StellarWP\Uplink\License\Manager\Pipeline\Processors\Multisite_Subdomain;
use StellarWP\Uplink\License\Manager\Pipeline\Processors\Multisite_Subfolder;
use StellarWP\Uplink\Pipeline\Pipeline;

final class Provider extends Abstract_Provider {

	/**
	 * @inheritDoc
	 *
	 * @throws RuntimeException
	 */
	public function register(): void {
		$this->register_license_handler();
	}

	/**
	 * Register the license handler and its pipeline to detect different
	 * multisite licenses.
	 *
	 * @return void
	 */
	private function register_license_handler(): void {
		$pipeline = ( new Pipeline( $this->container ) )->through( [
			Multisite_Main_Site::class,
			Multisite_Subfolder::class,
			Multisite_Subdomain::class,
			Multisite_Domain_Mapping::class,
		] );

		$this->container->singleton(
			License_Handler::class,
			static function () use ( $pipeline ) {
				return new License_Handler( $pipeline );
			}
		);
	}

}
