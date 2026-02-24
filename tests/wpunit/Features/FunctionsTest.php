<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Features;

use StellarWP\Uplink\Features\API\Client;
use StellarWP\Uplink\Features\Collection;
use StellarWP\Uplink\Features\Contracts\Feature_Strategy;
use StellarWP\Uplink\Features\Manager;
use StellarWP\Uplink\Features\Strategy\Resolver;
use StellarWP\Uplink\Features\Types\Feature;
use StellarWP\Uplink\Tests\UplinkTestCase;
use function StellarWP\Uplink\is_feature_available;
use function StellarWP\Uplink\is_feature_enabled;

final class FunctionsTest extends UplinkTestCase {

	/**
	 * Sets up a manager with a mocked active feature before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$collection = new Collection();
		$collection->add( $this->makeEmpty( Feature::class, [ 'get_slug' => 'test-feature' ] ) );

		$mock_strategy = $this->makeEmpty( Feature_Strategy::class, [
			'enable'    => true,
			'disable'   => true,
			'is_active' => true,
		] );

		$resolver = $this->makeEmpty( Resolver::class, [
			'resolve' => $mock_strategy,
		] );

		$catalog = $this->makeEmpty( Client::class, [
			'get_features' => $collection,
		] );

		$manager = new Manager( $catalog, $resolver );

		$this->container->bind( Manager::class, static function () use ( $manager ) {
			return $manager;
		} );
	}

	/**
	 * Tests is_feature_enabled returns true for an active feature in the catalog.
	 *
	 * @return void
	 */
	public function test_is_feature_enabled_returns_true_for_active_feature(): void {
		$this->assertTrue( is_feature_enabled( 'test-feature' ) );
	}

	/**
	 * Tests is_feature_enabled returns false for a feature not in the catalog.
	 *
	 * @return void
	 */
	public function test_is_feature_enabled_returns_false_for_unknown_feature(): void {
		$this->assertFalse( is_feature_enabled( 'nonexistent' ) );
	}

	/**
	 * Tests is_feature_available returns true for a feature present in the catalog.
	 *
	 * @return void
	 */
	public function test_is_feature_available_returns_true_for_catalog_feature(): void {
		$this->assertTrue( is_feature_available( 'test-feature' ) );
	}

	/**
	 * Tests is_feature_available returns false for a feature not in the catalog.
	 *
	 * @return void
	 */
	public function test_is_feature_available_returns_false_for_unknown_feature(): void {
		$this->assertFalse( is_feature_available( 'nonexistent' ) );
	}
}
