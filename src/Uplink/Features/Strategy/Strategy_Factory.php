<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Strategy;

use InvalidArgumentException;
use StellarWP\Uplink\Features\Contracts\Strategy;
use StellarWP\Uplink\Features\Types\Feature;
use StellarWP\Uplink\Features\Types\Plugin;
use StellarWP\Uplink\Features\Types\Theme;

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
		switch ( $feature->get_type() ) {
			case Feature::TYPE_PLUGIN:
				if ( ! $feature instanceof Plugin ) {
					throw new InvalidArgumentException(
						sprintf( 'Feature type "%s" requires a Plugin instance.', Feature::TYPE_PLUGIN )
					);
				}

				return new Plugin_Strategy( $feature );
			case Feature::TYPE_FLAG:
				return new Flag_Strategy( $feature );
			case Feature::TYPE_THEME:
				if ( ! $feature instanceof Theme ) {
					throw new InvalidArgumentException(
						sprintf( 'Feature type "%s" requires a Theme instance.', Feature::TYPE_THEME )
					);
				}

				return new Theme_Strategy( $feature );
			default:
				throw new InvalidArgumentException(
					sprintf( 'No strategy for feature type "%s".', $feature->get_type() )
				);
		}
	}
}
