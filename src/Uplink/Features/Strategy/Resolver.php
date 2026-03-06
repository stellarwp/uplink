<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Strategy;

use InvalidArgumentException;
use StellarWP\Uplink\Features\Contracts\Strategy;
use StellarWP\Uplink\Features\Types\Feature;

/**
 * Factory that creates Strategy instances for features.
 *
 * Maps feature type strings to factory callables. Each factory receives
 * the Feature and returns a Strategy instance bound to that Feature.
 *
 * @since 3.0.0
 */
class Resolver {

	/**
	 * Map of feature type strings to factory callables.
	 *
	 * Each callable has the signature: fn(Feature): Strategy
	 *
	 * @since 3.0.0
	 *
	 * @var array<string, callable(Feature): Strategy>
	 */
	private array $map = [];

	/**
	 * Registers a factory callable for a feature type.
	 *
	 * @since 3.0.0
	 *
	 * @param string   $type    The feature type identifier (e.g. 'plugin', 'theme', 'flag').
	 * @param callable $factory A callable that accepts a Feature and returns a Strategy.
	 *
	 * @return void
	 */
	public function register( string $type, callable $factory ): void {
		$this->map[ $type ] = $factory;
	}

	/**
	 * Creates the correct strategy for a given feature.
	 *
	 * @since 3.0.0
	 *
	 * @param Feature $feature The feature to create a strategy for.
	 *
	 * @throws InvalidArgumentException If no strategy is registered for the feature's type.
	 *
	 * @return Strategy A new Strategy instance bound to the given Feature.
	 */
	public function resolve( Feature $feature ): Strategy {
		$type    = $feature->get_type();
		$factory = $this->map[ $type ] ?? null;

		if ( $factory === null ) {
			throw new InvalidArgumentException(
				sprintf( 'No strategy registered for feature type "%s".', $type )
			);
		}

		return ( $factory )( $feature );
	}
}
