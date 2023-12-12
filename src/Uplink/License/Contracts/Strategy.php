<?php declare( strict_types=1 );

namespace StellarWP\Uplink\License\Contracts;

use StellarWP\Uplink\Pipeline\Pipeline;

/**
 * The base license key strategy.
 */
abstract class Strategy implements License_Key_Fetching_Strategy {

	/**
	 * The Pipeline instance used to get tokens from different storage
	 * locations.
	 *
	 * @var Pipeline
	 */
	protected $pipeline;

	/**
	 * @param  Pipeline  $pipeline
	 */
	public function __construct( Pipeline $pipeline ) {
		$this->pipeline = $pipeline;
	}

}
