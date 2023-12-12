<?php declare( strict_types=1 );

namespace StellarWP\Uplink\License;

use RuntimeException;
use StellarWP\ContainerContract\ContainerInterface;
use StellarWP\Uplink\Auth\License\License_Manager;
use StellarWP\Uplink\Config;
use StellarWP\Uplink\Enums\License_Strategy;
use StellarWP\Uplink\License\Contracts\License_Key_Fetching_Strategy;
use StellarWP\Uplink\License\Strategies\Global_License_Key_Strategy;
use StellarWP\Uplink\License\Strategies\Network_Only_License_Key_Strategy;
use StellarWP\Uplink\License\Strategies\Single_Site_License_Key_Strategy;
use StellarWP\Uplink\Resources\Resource;

final class License_Key_Strategy_Factory {

	/**
	 * @var ContainerInterface
	 */
	private $container;

	/**
	 * @var License_Manager
	 */
	private $license_manager;

	/**
	 * @param  ContainerInterface  $container
	 * @param  License_Manager     $license_manager
	 */
	public function __construct(
		ContainerInterface $container,
		License_Manager $license_manager
	) {
		$this->container       = $container;
		$this->license_manager = $license_manager;
	}

	/**
	 * Make a license key fetching strategy based on the Uplink license key strategy
	 * and the multisite site license state.
	 *
	 * You should use the License Key Fetcher.
	 *
	 * @see License_Key_Fetcher::get_key()
	 *
	 * @throws RuntimeException
	 */
	public function make( Resource $resource ): License_Key_Fetching_Strategy {
		switch( Config::get_license_key_strategy() ) {
			case License_Strategy::GLOBAL:
				return $this->container->get( Global_License_Key_Strategy::class );

			case License_Strategy::ISOLATED:
				$network = $this->license_manager->allows_multisite_license( $resource );
				$class   = $network ? Network_Only_License_Key_Strategy::class : Single_Site_License_Key_Strategy::class;

				return $this->container->get( $class );

			default:
				throw new RuntimeException( 'Invalid config license strategy provided. See Config::set_license_key_strategy()' );
		}
	}

}
