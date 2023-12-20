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
	 * Whether the key was the original/default file based key.
	 *
	 * @var bool
	 */
	private $is_original_key = false;

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
	 * Whether the key was the original file based key.
	 *
	 * @return bool
	 */
	public function is_original(): bool {
		return $this->is_original_key;
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
			$this->is_original_key = true;
			$license_key           = $this->get_from_file( $resource );
		}

		return $license_key;
	}

	/**
	 * Get a license key from the packaged class that came with the plugin (if provided).
	 *
	 * @param  Resource  $resource
	 *
	 * @return string|null
	 */
	public function get_from_file( Resource $resource ): ?string {
		return $this->file->get( $resource );
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
