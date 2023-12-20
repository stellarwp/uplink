<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Auth\Token;

use StellarWP\ContainerContract\ContainerInterface;
use StellarWP\Uplink\Auth\Token\Contracts\Token_Manager;
use StellarWP\Uplink\Auth\Token\Managers\Network_Token_Manager;
use StellarWP\Uplink\Resources\Resource;

final class Token_Factory {

	/**
	 * @var ContainerInterface
	 */
	private $container;

	/**
	 * @param  ContainerInterface  $container
	 */
	public function __construct( ContainerInterface $container ) {
		$this->container = $container;
	}

	/**
	 * Makes Network or Single Site Token Manager instance.
	 *
	 * @param  Resource  $resource  The resource to check against.
	 *
	 * @return Token_Manager
	 */
	public function make( Resource $resource ): Token_Manager {
		$network_license = $resource->uses_network_licensing();

		return $this->container->get( $network_license ? Network_Token_Manager::class : Managers\Token_Manager::class );
	}

}
