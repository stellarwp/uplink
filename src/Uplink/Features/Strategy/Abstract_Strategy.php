<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Strategy;

use StellarWP\Uplink\Features\Contracts\Strategy;
use StellarWP\Uplink\Features\Types\Feature;

/**
 * Base class for feature-gating strategies.
 *
 * Each strategy instance is bound to a single Feature at construction time.
 *
 * @since 3.0.0
 */
abstract class Abstract_Strategy implements Strategy {

	/**
	 * The feature this strategy operates on.
	 *
	 * @since 3.0.0
	 *
	 * @var Feature
	 */
	protected Feature $feature;

	/**
	 * Construct the strategy.
	 *
	 * @since 3.0.0
	 *
	 * @param Feature $feature The feature this strategy operates on.
	 */
	public function __construct( Feature $feature ) {
		$this->feature = $feature;
	}
}
