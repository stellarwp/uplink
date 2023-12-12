<?php declare( strict_types=1 );

namespace StellarWP\Uplink\License\Contracts;

use StellarWP\Uplink\Resources\Resource;

interface License_Key_Fetching_Strategy {

	/**
	 * Get a license key from the current strategy.
	 *
	 * @param  Resource  $resource The resource associated with the license key.
	 *
	 * @return string|null
	 */
	public function get_key( Resource $resource ): ?string;

}
