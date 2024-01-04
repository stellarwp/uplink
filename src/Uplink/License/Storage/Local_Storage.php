<?php declare( strict_types=1 );

namespace StellarWP\Uplink\License\Storage;

use StellarWP\Uplink\License\Storage\Traits\Option_Name_Trait;
use StellarWP\Uplink\Resources\Resource;
use StellarWP\Uplink\Utils\Sanitize;

/**
 * Manages license keys for the current site.
 */
final class Local_Storage implements Contracts\Storage {

	use Option_Name_Trait;

	/**
	 * @inheritDoc
	 */
	public function store( Resource $resource, string $license_key ): bool {
		$license_key = Sanitize::key( $license_key );

		// WordPress would otherwise return false if the items match.
		if ( $license_key === $this->get( $resource ) ) {
			return true;
		}

		return update_option( self::option_name( $resource ), $license_key );
	}

	/**
	 * @inheritDoc
	 */
	public function get( Resource $resource ): ?string {
		return get_option( self::option_name( $resource ), null );
	}

	/**
	 * @inheritDoc
	 */
	public function delete( Resource $resource ): bool {
		return delete_option( self::option_name( $resource ) );
	}

}
