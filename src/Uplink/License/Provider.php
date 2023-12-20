<?php declare( strict_types=1 );

namespace StellarWP\Uplink\License;

use RuntimeException;
use StellarWP\Uplink\Contracts\Abstract_Provider;
use StellarWP\Uplink\License\Contracts\License_Key_Fetching_Strategy;
use StellarWP\Uplink\License\Manager\License_Handler;
use StellarWP\Uplink\License\Manager\Pipeline\Processors\Multisite_Domain_Mapping;
use StellarWP\Uplink\License\Manager\Pipeline\Processors\Multisite_Main_Site;
use StellarWP\Uplink\License\Manager\Pipeline\Processors\Multisite_Subdomain;
use StellarWP\Uplink\License\Manager\Pipeline\Processors\Multisite_Subfolder;
use StellarWP\Uplink\License\Strategies\Global_License_Key_Strategy;
use StellarWP\Uplink\License\Strategies\Network_Only_License_Key_Strategy;
use StellarWP\Uplink\License\Strategies\Single_Site_License_Key_Strategy;
use StellarWP\Uplink\Pipeline\Pipeline;

final class Provider extends Abstract_Provider {

	public const LICENSE_PIPELINE = 'uplink.license.strategy.pipeline';

	/**
	 * @inheritDoc
	 *
	 * @throws RuntimeException
	 */
	public function register(): void {
		$this->register_license_manager();
		$this->register_license_strategies();
	}

	/**
	 * Register the license manager and its pipeline to detect different
	 * multisite licenses.
	 *
	 * @return void
	 */
	private function register_license_manager(): void {
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

	/**
	 * Register all available license key strategies.
	 *
	 * @throws RuntimeException
	 *
	 * @return void
	 */
	private function register_license_strategies(): void {
		$this->container->singleton( self::LICENSE_PIPELINE, new Pipeline( $this->container ) );

		$this->container->singleton(
			Global_License_Key_Strategy::class,
			static function( $c ): License_Key_Fetching_Strategy {
				return new Global_License_Key_Strategy( $c->get( self::LICENSE_PIPELINE ) );
			}
		);

		$this->container->singleton(
			Network_Only_License_Key_Strategy::class,
			static function( $c ): License_Key_Fetching_Strategy {
				return new Network_Only_License_Key_Strategy( $c->get( self::LICENSE_PIPELINE ) );
			}
		);

		$this->container->singleton(
			Single_Site_License_Key_Strategy::class,
			static function( $c ): License_Key_Fetching_Strategy {
				return new Single_Site_License_Key_Strategy( $c->get( self::LICENSE_PIPELINE ) );
			}
		);
	}

}
