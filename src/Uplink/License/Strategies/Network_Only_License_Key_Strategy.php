<?php declare( strict_types=1 );

namespace StellarWP\Uplink\License\Strategies;

use StellarWP\Uplink\License\Contracts\Strategy;
use StellarWP\Uplink\License\Strategies\Pipeline\License_Traveler;
use StellarWP\Uplink\License\Strategies\Pipeline\Processors\File;
use StellarWP\Uplink\License\Strategies\Pipeline\Processors\Network;
use StellarWP\Uplink\Resources\Resource;

/**
 * Get a network level licence key.
 *
 * Check network > fallback to file license (if included).
 */
final class Network_Only_License_Key_Strategy extends Strategy {

	/**
	 * Get a network license key.
	 *
	 * @param  Resource  $resource
	 *
	 * @return string|null
	 */
	public function get_key( Resource $resource ): ?string {
		/** @var License_Traveler $result */
		$result = $this->pipeline->through( [
			Network::class,
			File::class,
		] )->send( new License_Traveler( $resource ) )->thenReturn();

		return $result->licence_key;
	}

}
