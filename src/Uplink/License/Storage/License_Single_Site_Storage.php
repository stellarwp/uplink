<?php declare( strict_types=1 );

namespace StellarWP\Uplink\License\Storage;

use StellarWP\Uplink\Resources\Resource;
use StellarWP\Uplink\Utils\Sanitize;

/**
 * Manages license keys for the current site.
 */
final class License_Single_Site_Storage implements Contracts\Storage {

	/**
	 * @inheritDoc
	 */
	public function store( Resource $resource, string $license_key ): bool {
		$license_key = Sanitize::key( $license_key );

		// WordPress would otherwise return false if the items match.
		if ( $license_key === $this->get( $resource ) ) {
			return true;
		}

		return update_option( $this->option_name( $resource ), $license_key );
	}

	/**
	 * @inheritDoc
	 */
	public function get( Resource $resource ): ?string {
		return get_option( $this->option_name( $resource ), null );
	}

	/**
	 * @inheritDoc
	 */
	public function delete( Resource $resource ): bool {
		return delete_option( $this->option_name( $resource ) );
	}

	/**
	 * Get the unique option name to save in options table for the
	 * current time.
	 *
	 * @param  Resource  $resource
	 *
	 * @return string
	 */
	private function option_name( Resource $resource ): string {
		return self::KEY_PREFIX . $resource->get_slug();
	}

}
