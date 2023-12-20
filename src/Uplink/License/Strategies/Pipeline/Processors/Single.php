<?php declare( strict_types=1 );

namespace StellarWP\Uplink\License\Strategies\Pipeline\Processors;

use Closure;
use StellarWP\Uplink\License\Storage\Local_Storage;
use StellarWP\Uplink\License\Strategies\Pipeline\License_Traveler;

final class Single {

	/**
	 * @var Local_Storage
	 */
	private $storage;

	/**
	 * @param  Local_Storage  $storage
	 */
	public function __construct( Local_Storage $storage ) {
		$this->storage = $storage;
	}

	/**
	 * Attempt to get a license key from the current site (multisite or not).
	 *
	 * @param  License_Traveler  $traveler The instance passed through the pipeline.
	 * @param  Closure           $next The next step in the pipeline.
	 *
	 * @return License_Traveler
	 */
	public function __invoke( License_Traveler $traveler, Closure $next ): License_Traveler {
		if ( ! $traveler->licence_key ) {
			$traveler->licence_key = $this->storage->get( $traveler->resource() );
		}

		return $next( $traveler );
	}

}
