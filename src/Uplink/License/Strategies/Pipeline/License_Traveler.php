<?php declare( strict_types=1 );

namespace StellarWP\Uplink\License\Strategies\Pipeline;

use StellarWP\Uplink\Resources\Resource;

/**
 * Passed through license strategy pipeline and modified
 * as license keys are found using different strategies.
 */
final class License_Traveler {

	/**
	 * @var string|null
	 */
	public $licence_key = null;

	/**
	 * @var Resource
	 */
	private $resource;

	public function __construct( Resource $resource ) {
		$this->resource = $resource;
	}

	public function resource(): Resource {
		return $this->resource;
	}

}
