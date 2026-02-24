<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Strategy;

use InvalidArgumentException;
use StellarWP\ContainerContract\ContainerInterface;
use StellarWP\Uplink\Features\Contracts\Feature_Strategy;
use StellarWP\Uplink\Features\Types\Feature;

/**
 * Maps feature type strings to Feature_Strategy implementations.
 *
 * New types can be added via register() or by filtering the
 * 'stellarwp/uplink/feature_strategy_map' hook.
 *
 * @since TBD
 */
class Resolver {

	/**
	 * The DI container.
	 *
	 * @since TBD
	 *
	 * @var ContainerInterface
	 */
	private ContainerInterface $container;

	/**
	 * Map of feature type strings to strategy class names.
	 *
	 * @since TBD
	 *
	 * @var array<string, class-string<Feature_Strategy>>
	 */
	private array $map = [];

	/**
	 * Constructor for the feature type to strategy map resolver.
	 *
	 * @since TBD
	 *
	 * @param ContainerInterface $container The DI container.
	 *
	 * @return void
	 */
	public function __construct( ContainerInterface $container ) {
		$this->container = $container;
	}

	/**
	 * Register a strategy class for a feature type.
	 *
	 * @since TBD
	 *
	 * @param string                       $type           The feature type identifier (e.g. 'zip', 'built_in').
	 * @param class-string<Feature_Strategy> $strategy_class The strategy FQCN.
	 *
	 * @return void
	 */
	public function register( string $type, string $strategy_class ): void {
		$this->map[ $type ] = $strategy_class;
	}

	/**
	 * Resolve the correct strategy for a given feature.
	 *
	 * The internal map is filtered through 'stellarwp/uplink/feature_strategy_map'
	 * before lookup, allowing consumers to add custom strategies.
	 *
	 * @since TBD
	 *
	 * @param Feature $feature The feature to resolve a strategy for.
	 *
	 * @throws InvalidArgumentException If no strategy is registered for the feature's type.
	 *
	 * @return Feature_Strategy
	 */
	public function resolve( Feature $feature ): Feature_Strategy {
		/**
		 * Filters the feature type to strategy class map.
		 *
		 * @since TBD
		 *
		 * @param array<string, class-string<Feature_Strategy>> $map The current type map.
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
