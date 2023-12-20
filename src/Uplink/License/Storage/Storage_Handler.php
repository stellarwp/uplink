<?php declare( strict_types=1 );

namespace StellarWP\Uplink\License\Storage;

use StellarWP\Uplink\License\Storage\Contracts\Storage;
use StellarWP\Uplink\Resources\Resource;

/**
 * A network licensing aware license key storage handler.
 *
 * You should use this if you want to automate managing a license key
 * from the correct storage location.
 */
final class Storage_Handler implements Storage {

	/**
	 * @var Storage_Factory
	 */
	private $factory;

	/**
	 * @var File_Storage
	 */
	private $file;

	/**
	 * @param  Storage_Factory  $factory
	 * @param  File_Storage     $file
	 */
	public function __construct(
		Storage_Factory $factory,
		File_Storage $file
	) {
		$this->factory = $factory;
		$this->file = $file;
	}

	/**
	 * Store a license key in either site_options or the site's options table.
	 *
	 * @param  Resource  $resource
	 * @param  string    $license_key
	 *
	 * @return bool
	 */
	public function store( Resource $resource, string $license_key ): bool {
		return $this->factory->make( $resource )->store( $resource, $license_key );
	}

	/**
	 * Get a license key from either site_options or the site's options table.
	 *
	 * @param  Resource  $resource
	 *
	 * @return string|null
	 */
	public function get( Resource $resource ): ?string {
		$license_key = $this->factory->make( $resource )->get( $resource );

		// Fallback to the original file based storage key.
		if ( ! $license_key ) {
			$license_key = $this->file->get( $resource );
		}

		return $license_key;
	}

	/**
	 * Delete a license key from either site_options or the site's options table.
	 *
	 * @param  Resource  $resource
	 *
	 * @return bool
	 */
	public function delete( Resource $resource ): bool {
		return $this->factory->make( $resource )->delete( $resource );
	}

}
