<?php declare( strict_types=1 );

namespace StellarWP\Uplink\License\Storage;

use StellarWP\Uplink\Resources\Resource;
use StellarWP\Uplink\Utils\Sanitize;

/**
 * Manages license keys in a WordPress network.
 */
final class License_Network_Storage implements Contracts\Storage {

	/**
	 * @inheritDoc
	 */
	public function store( Resource $resource, string $license_key ): bool {
		if ( ! $this->is_multisite( $resource ) ) {
			return false;
		}

		$license_key = Sanitize::key( $license_key );

		// WordPress would otherwise return false if the items match.
		if ( $license_key === $this->get( $resource ) ) {
			return true;
		}

		return update_site_option( $this->option_name( $resource ), $license_key );
	}

	/**
	 * @inheritDoc
	 */
	public function get( Resource $resource ): ?string {
		if ( ! $this->is_multisite( $resource ) ) {
			return null;
		}

		return get_site_option( $this->option_name( $resource ), null );
	}

	/**
	 * @inheritDoc
	 */
	public function delete( Resource $resource ): bool {
		if ( ! $this->is_multisite( $resource ) ) {
			return false;
		}

		return delete_site_option( $this->option_name( $resource ) );
	}

	/**
	 * Get the unique option name to save in the network.
	 *
	 * @param  Resource  $resource
	 *
	 * @return string
	 */
	private function option_name( Resource $resource ): string {
		return self::KEY_PREFIX . $resource->get_slug();
	}

	/**
	 * Determine if we can even store or fetch license keys.
	 *
	 * @param  Resource  $resource
	 *
	 * @return bool
	 */
	private function is_multisite( Resource $resource ): bool {
		return is_multisite() && $resource->is_network_activated();
	}

}
