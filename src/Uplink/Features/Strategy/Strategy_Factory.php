<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Strategy;

use InvalidArgumentException;
use StellarWP\Uplink\Features\Contracts\Strategy;
use StellarWP\Uplink\Features\Types\Feature;

/**
 * Factory that creates Strategy instances for features.
 *
 * Maps feature type strings to their corresponding Strategy classes.
 * Each call creates a new Strategy instance bound to the given Feature.
 *
 * @since 3.0.0
 */
class Strategy_Factory {

	/**
	 * Creates the correct strategy for a given feature.
	 *
	 * @since 3.0.0
	 *
	 * @param Feature $feature The feature to create a strategy for.
	 *
	 * @throws InvalidArgumentException If no strategy exists for the feature's type.
	 *
	 * @return Strategy A new Strategy instance bound to the given Feature.
	 */
	public function make( Feature $feature ): Strategy {
		return match ( $feature->get_type() ) {
			'plugin' => new Plugin_Strategy( $feature ),
			'flag'   => new Flag_Strategy( $feature ),
			'theme'  => new Theme_Strategy( $feature ),
			default  => throw new InvalidArgumentException(
				sprintf( 'No strategy for feature type "%s".', $feature->get_type() )
			),
		};
	}
}
