<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Auth\Token;

use StellarWP\ContainerContract\ContainerInterface;
use StellarWP\Uplink\Auth\Token\Contracts\Token_Manager;
use StellarWP\Uplink\Auth\Token\Managers\Network_Token_Manager;

final class Token_Manager_Factory {

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
	 * @param  bool  $network Whether to store the token in Network Site Meta, or a single sites options table.
	 *
	 * @return Token_Manager
	 */
	public function make( bool $network = false ): Token_Manager {
		$class = $network ? Network_Token_Manager::class : Managers\Token_Manager::class;

		return $this->container->get( $class );
	}

}
