<?php declare( strict_types=1 );

namespace StellarWP\Uplink\License\Strategies;

use StellarWP\Uplink\License\Contracts\Strategy;
use StellarWP\Uplink\License\Strategies\Pipeline\License_Traveler;
use StellarWP\Uplink\License\Strategies\Pipeline\Processors\File;
use StellarWP\Uplink\License\Strategies\Pipeline\Processors\Single;
use StellarWP\Uplink\Resources\Resource;

/**
 * Get a single site license key.
 *
 * Check single site > fallback to file license (if included).
 */
final class Single_Site_License_Key_Strategy extends Strategy {

	/**
	 * Get a license key for the current site (multisite or not).
	 *
	 * @param  Resource  $resource
	 *
	 * @return string|null
	 */
	public function get_key( Resource $resource ): ?string {
		/** @var License_Traveler $result */
		$result = $this->pipeline->through( [
			Single::class,
			File::class,
		] )->send( new License_Traveler( $resource ) )->thenReturn();

		return $result->licence_key;
	}

}