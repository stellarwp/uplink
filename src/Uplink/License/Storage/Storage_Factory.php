<?php declare( strict_types=1 );

namespace StellarWP\Uplink\License\Storage;

use StellarWP\ContainerContract\ContainerInterface;
use StellarWP\Uplink\License\Storage\Contracts\Storage;
use StellarWP\Uplink\Resources\Resource;

/**
 * Make the correct storage object based on whether the resource
 * supports network licensing.
 */
final class Storage_Factory {

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
	 * Make a storage instance that either stores in site_options or the local options tables.
	 *
	 * @param  Resource  $resource
	 *
	 * @return Storage
	 */
	public function make( Resource $resource ): Storage {
		$class = $resource->uses_network_licensing() ? Network_Storage::class : Local_Storage::class;

		return $this->container->get( $class );
	}

}
