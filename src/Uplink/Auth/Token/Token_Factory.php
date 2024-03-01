<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Auth\Token;

use StellarWP\ContainerContract\ContainerInterface;
use StellarWP\Uplink\Auth\License\License_Manager;
use StellarWP\Uplink\Auth\Token\Contracts\Token_Manager;
use StellarWP\Uplink\Auth\Token\Managers\Network_Token_Manager;
use StellarWP\Uplink\Resources\Resource;

final class Token_Factory {

	/**
	 * @var License_Manager
	 */
	private $license_manager;

	/**
	 * @var ContainerInterface
	 */
	private $container;

	/**
	 * @param  License_Manager  $license_manager
	 * @param  ContainerInterface  $container
	 */
	public function __construct( License_Manager $license_manager, ContainerInterface $container ) {
		$this->license_manager = $license_manager;
		$this->container       = $container;
	}

	/**
	 * Makes Network or Single Site Token Manager instance.
	 *
	 * @param  Resource  $resource  The resource to check against.
	 *
	 * @return Token_Manager
	 */
	public function make( Resource $resource ): Token_Manager {
		$network_license = $this->license_manager->allows_multisite_license( $resource );

		return $this->container->get( $network_license ? Network_Token_Manager::class : Managers\Token_Manager::class );
	}

}
