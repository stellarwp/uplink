<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Features\Strategy;

use InvalidArgumentException;
use StellarWP\Uplink\Features\Contracts\Feature_Strategy;
use StellarWP\Uplink\Features\Strategy\Resolver;
use StellarWP\Uplink\Features\Types\Feature;
use StellarWP\Uplink\Tests\UplinkTestCase;

final class ResolverTest extends UplinkTestCase {

	/**
	 * The strategy resolver instance under test.
	 *
	 * @var Resolver
	 */
	private Resolver $resolver;

	/**
	 * Sets up the strategy resolver before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->resolver = new Resolver( $this->container );
	}

	/**
	 * Tests that a registered strategy is resolved for the matching feature type.
	 *
	 * @return void
	 */
	public function test_it_resolves_registered_strategy(): void {
		$mock_strategy = $this->makeEmpty( Feature_Strategy::class );

		$this->container->bind( get_class( $mock_strategy ), static function () use ( $mock_strategy ) {
			return $mock_strategy;
		} );

		$this->resolver->register( 'test-type', get_class( $mock_strategy ) );

		$feature  = $this->makeEmpty( Feature::class, [ 'get_type' => 'test-type' ] );
		$resolved = $this->resolver->resolve( $feature );

		$this->assertSame( $mock_strategy, $resolved );
	}

	/**
	 * Tests that an exception is thrown when no strategy is registered for a feature type.
	 *
	 * @return void
	 */
	public function test_it_throws_for_unregistered_type(): void {
		$feature = $this->makeEmpty( Feature::class, [ 'get_type' => 'unregistered' ] );

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'No strategy registered for feature type "unregistered".' );

		$this->resolver->resolve( $feature );
	}

	/**
	 * Tests that the strategy map can be modified via the WordPress filter.
	 *
	 * @return void
	 */
	public function test_strategy_map_is_filterable(): void {
		$mock_strategy = $this->makeEmpty( Feature_Strategy::class );

		$this->container->bind( get_class( $mock_strategy ), static function () use ( $mock_strategy ) {
			return $mock_strategy;
		} );

		add_filter( 'stellarwp/uplink/feature_strategy_map', static function ( $map ) use ( $mock_strategy ) {
			$map['test-type'] = get_class( $mock_strategy );

			return $map;
		} );

		$feature  = $this->makeEmpty( Feature::class, [ 'get_type' => 'test-type' ] );
		$resolved = $this->resolver->resolve( $feature );

		$this->assertSame( $mock_strategy, $resolved );
	}
}
