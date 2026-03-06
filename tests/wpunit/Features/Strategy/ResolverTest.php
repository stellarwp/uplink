<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Features\Strategy;

use InvalidArgumentException;
use StellarWP\Uplink\Features\Contracts\Strategy;
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

		$this->resolver = new Resolver();
	}

	/**
	 * Tests that a registered factory is invoked for the matching feature type.
	 *
	 * @return void
	 */
	public function test_it_resolves_registered_strategy(): void {
		$mock_strategy = $this->makeEmpty( Strategy::class );

		$this->resolver->register(
			'test-type',
			static fn( Feature $f ) => $mock_strategy
		);

		$feature  = $this->makeEmpty( Feature::class, [ 'get_type' => 'test-type' ] );
		$resolved = $this->resolver->resolve( $feature );

		$this->assertSame( $mock_strategy, $resolved );
	}

	/**
	 * Tests that the factory receives the Feature being resolved.
	 *
	 * @return void
	 */
	public function test_it_passes_feature_to_factory(): void {
		$received_feature = null;

		$this->resolver->register(
			'test-type',
			function ( Feature $f ) use ( &$received_feature ) {
				$received_feature = $f;

				return $this->makeEmpty( Strategy::class );
			}
		);

		$feature = $this->makeEmpty( Feature::class, [ 'get_type' => 'test-type' ] );
		$this->resolver->resolve( $feature );

		$this->assertSame( $feature, $received_feature );
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
}
