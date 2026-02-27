<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Strategy;

use InvalidArgumentException;
use StellarWP\ContainerContract\ContainerInterface;
use StellarWP\Uplink\Features\Contracts\Strategy;
use StellarWP\Uplink\Features\Types\Feature;

/**
 * Maps feature type strings to Strategy implementations.
 * New types can be added via register().
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
	private ContainerInterface $container;

	/**
	 * Map of feature type strings to strategy class names.
	 *
	 * @since 3.0.0
	 *
	 * @var array<string, class-string<Strategy>>
	 */
	private array $map = [];

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
	public function register( string $type, string $strategy_class ): void { // phpcs:ignore Squiz.Commenting.FunctionComment.IncorrectTypeHint -- class-string<Strategy> is a PHPStan type narrowing.
		$this->map[ $type ] = $strategy_class;
	}

	/**
	 * Resolves the correct strategy for a given feature.
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
		$type  = $feature->get_type();
		$class = $this->map[ $type ] ?? null;

		if ( $class === null ) {
			throw new InvalidArgumentException(
				sprintf( 'No strategy registered for feature type "%s".', $type )
			);
		}

		/** @var Strategy $strategy */
		$strategy = $this->container->get( $class );

		return $strategy;
	}
}
