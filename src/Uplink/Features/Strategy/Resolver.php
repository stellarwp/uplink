<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Strategy;

use InvalidArgumentException;
use StellarWP\ContainerContract\ContainerInterface;
use StellarWP\Uplink\Features\Contracts\Strategy;
use StellarWP\Uplink\Features\Types\Feature;

/**
 * Maps feature type strings to Strategy implementations.
 *
 * New types can be added via register() or by filtering the
 * 'stellarwp/uplink/feature_strategy_map' hook.
 *
 * @since 3.0.0
 */
class Resolver {

	/**
	 * The DI container.
	 *
	 * @since 3.0.0
	 *
	 * @var ContainerInterface
	 */
	private $container;

	/**
	 * Map of feature type strings to strategy class names.
	 *
	 * @since 3.0.0
	 *
	 * @var array<string, class-string<Strategy>>
	 */
	private $map = [];

	/**
	 * Constructor for the feature type to strategy map resolver.
	 *
	 * @since 3.0.0
	 *
	 * @param ContainerInterface $container The DI container.
	 *
	 * @return void
	 */
	public function __construct( ContainerInterface $container ) {
		$this->container = $container;
	}

	/**
	 * Registers a strategy class for a feature type.
	 *
	 * @since 3.0.0
	 *
	 * @param string                 $type           The feature type identifier (e.g. 'zip', 'built_in').
	 * @param class-string<Strategy> $strategy_class The strategy FQCN.
	 *
	 * @return void
	 */
	public function register( string $type, string $strategy_class ): void {
		$this->map[ $type ] = $strategy_class;
	}

	/**
	 * Resolves the correct strategy for a given feature.
	 *
	 * The internal map is filtered through 'stellarwp/uplink/feature_strategy_map'
	 * before lookup, allowing consumers to add custom strategies.
	 *
	 * @since 3.0.0
	 *
	 * @param Feature $feature The feature to resolve a strategy for.
	 *
	 * @throws InvalidArgumentException If no strategy is registered for the feature's type.
	 *
	 * @return Strategy
	 */
	public function resolve( Feature $feature ): Strategy {
		/**
		 * Filters the feature type to strategy class map.
		 *
		 * @since 3.0.0
		 *
		 * @param array<string, class-string<Strategy>> $map The current type map.
		 *
		 * @return array<string, class-string<Strategy>> The filtered type map.
		 */
		$map = apply_filters( 'stellarwp/uplink/feature_strategy_map', $this->map );

		$type  = $feature->get_type();
		$class = $map[ $type ] ?? null;

		if ( $class === null ) {
			throw new InvalidArgumentException(
				sprintf( 'No strategy registered for feature type "%s".', $type )
			);
		}

		return $this->container->get( $class );
	}
}
