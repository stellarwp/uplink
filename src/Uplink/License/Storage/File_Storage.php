<?php declare( strict_types=1 );

namespace StellarWP\Uplink\License\Storage;

use RuntimeException;
use StellarWP\Uplink\Resources\Resource;

/**
 * Manages licenses when included inside the plugin, via the
 * defined helper class.
 */
final class File_Storage implements Contracts\Storage {

	/**
	 * @inheritDoc
	 *
	 * @throws RuntimeException
	 */
	public function store( Resource $resource, string $license_key ): bool {
		throw new RuntimeException( 'You cannot save a license using file based storage' );
	}

	/**
	 * @inheritDoc
	 */
	public function get( Resource $resource ): ?string {
		$license_class = $resource->get_license_class();

		if ( empty( $license_class ) ) {
			return null;
		}

		$key = null;

		if ( defined( $license_class . '::KEY' ) ) {
			$key = $license_class::KEY;
		} elseif ( defined( $license_class . '::DATA' ) ) {
			$key = $license_class::DATA;
		}

		return $key;
	}

	/**
	 * @inheritDoc
	 *
	 * @throws RuntimeException
	 */
	public function delete( Resource $resource ): bool {
		throw new RuntimeException( 'You cannot delete a license using file based storage' );
	}

}
