<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Features;

use StellarWP\Uplink\Features\API\Client;
use StellarWP\Uplink\Features\Collection;
use StellarWP\Uplink\Features\Contracts\Strategy;
use StellarWP\Uplink\Features\Manager;
use StellarWP\Uplink\Features\Strategy\Resolver;
use StellarWP\Uplink\Features\Types\Feature;
use StellarWP\Uplink\Tests\UplinkTestCase;

final class ManagerTest extends UplinkTestCase {

	/**
	 * The feature manager instance under test.
	 *
	 * @var Manager
	 */
	private Manager $manager;

	/**
	 * The feature collection used by the manager.
	 *
	 * @var Collection
	 */
	private Collection $collection;

	/**
	 * The mocked feature strategy.
	 *
	 * @var Strategy
	 */
	private $mock_strategy;

	/**
	 * Sets up the manager with mocked dependencies before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->collection = new Collection();
		$this->collection->add( $this->makeEmpty( Feature::class, [ 'get_slug' => 'test-feature' ] ) );

		$catalog = $this->makeEmpty( Client::class, [
			'get_features' => $this->collection,
		] );

		$this->mock_strategy = $this->makeEmpty( Strategy::class, [
			'enable'    => true,
			'disable'   => true,
			'is_active' => true,
		] );

		$resolver = $this->makeEmpty( Resolver::class, [
			'resolve' => $this->mock_strategy,
		] );

		$this->manager = new Manager( $catalog, $resolver );
	}

	/**
	 * Tests a known feature can be enabled successfully.
	 *
	 * @return void
	 */
	public function test_it_enables_a_feature(): void {
		$result = $this->manager->enable( 'test-feature' );

		$this->assertTrue( $result );
	}

	/**
	 * Tests enabling an unknown feature returns false.
	 *
	 * @return void
	 */
	public function test_it_returns_false_when_enabling_unknown_feature(): void {
		$result = $this->manager->enable( 'nonexistent' );

		$this->assertFalse( $result );
	}

	/**
	 * Tests a known feature can be disabled successfully.
	 *
	 * @return void
	 */
	public function test_it_disables_a_feature(): void {
		$result = $this->manager->disable( 'test-feature' );

		$this->assertTrue( $result );
	}

	/**
	 * Tests disabling an unknown feature returns false.
	 *
	 * @return void
	 */
	public function test_it_returns_false_when_disabling_unknown_feature(): void {
		$result = $this->manager->disable( 'nonexistent' );

		$this->assertFalse( $result );
	}

	/**
	 * Tests is_enabled delegates to the strategy's is_active method.
	 *
	 * @return void
	 */
	public function test_is_enabled_checks_strategy(): void {
		$this->assertTrue( $this->manager->is_enabled( 'test-feature' ) );
	}

	/**
	 * Tests is_enabled returns false for a feature not in the catalog.
	 *
	 * @return void
	 */
	public function test_is_enabled_returns_false_for_unknown_feature(): void {
		$this->assertFalse( $this->manager->is_enabled( 'nonexistent' ) );
	}

	/**
	 * Tests is_available returns true for a feature present in the catalog.
	 *
	 * @return void
	 */
	public function test_is_available_returns_true_for_catalog_feature(): void {
		$this->assertTrue( $this->manager->is_available( 'test-feature' ) );
	}

	/**
	 * Tests is_available returns false for a feature not in the catalog.
	 *
	 * @return void
	 */
	public function test_is_available_returns_false_for_unknown_feature(): void {
		$this->assertFalse( $this->manager->is_available( 'nonexistent' ) );
	}

	/**
	 * Tests get_features returns the catalog collection.
	 *
	 * @return void
	 */
	public function test_get_features_returns_collection(): void {
		$features = $this->manager->get_features();

		$this->assertInstanceOf( Collection::class, $features );
		$this->assertSame( 1, $features->count() );
	}

	/**
	 * Tests enabling a feature fires global and slug-specific WordPress actions.
	 *
	 * @return void
	 */
	public function test_enable_fires_actions(): void {
		$enabling_fired      = false;
		$enabled_fired       = false;
		$slug_enabling_fired = false;
		$slug_enabled_fired  = false;

		add_action( 'stellarwp/uplink/feature_enabling', static function () use ( &$enabling_fired ) {
			$enabling_fired = true;
		} );

		add_action( 'stellarwp/uplink/feature_enabled', static function () use ( &$enabled_fired ) {
			$enabled_fired = true;
		} );

		add_action( 'stellarwp/uplink/test-feature/feature_enabling', static function () use ( &$slug_enabling_fired ) {
			$slug_enabling_fired = true;
		} );

		add_action( 'stellarwp/uplink/test-feature/feature_enabled', static function () use ( &$slug_enabled_fired ) {
			$slug_enabled_fired = true;
		} );

		$this->manager->enable( 'test-feature' );

		$this->assertTrue( $enabling_fired, 'Global feature_enabling action should have fired.' );
		$this->assertTrue( $enabled_fired, 'Global feature_enabled action should have fired.' );
		$this->assertTrue( $slug_enabling_fired, 'Slug-specific feature_enabling action should have fired.' );
		$this->assertTrue( $slug_enabled_fired, 'Slug-specific feature_enabled action should have fired.' );
	}

	/**
	 * Tests disabling a feature fires global and slug-specific WordPress actions.
	 *
	 * @return void
	 */
	public function test_disable_fires_actions(): void {
		$disabling_fired      = false;
		$disabled_fired       = false;
		$slug_disabling_fired = false;
		$slug_disabled_fired  = false;

		add_action( 'stellarwp/uplink/feature_disabling', static function () use ( &$disabling_fired ) {
			$disabling_fired = true;
		} );

		add_action( 'stellarwp/uplink/feature_disabled', static function () use ( &$disabled_fired ) {
			$disabled_fired = true;
		} );

		add_action( 'stellarwp/uplink/test-feature/feature_disabling', static function () use ( &$slug_disabling_fired ) {
			$slug_disabling_fired = true;
		} );

		add_action( 'stellarwp/uplink/test-feature/feature_disabled', static function () use ( &$slug_disabled_fired ) {
			$slug_disabled_fired = true;
		} );

		$this->manager->disable( 'test-feature' );

		$this->assertTrue( $disabling_fired, 'Global feature_disabling action should have fired.' );
		$this->assertTrue( $disabled_fired, 'Global feature_disabled action should have fired.' );
		$this->assertTrue( $slug_disabling_fired, 'Slug-specific feature_disabling action should have fired.' );
		$this->assertTrue( $slug_disabled_fired, 'Slug-specific feature_disabled action should have fired.' );
	}

	/**
	 * Tests that the feature_enabled action does not fire when the strategy fails to enable.
	 *
	 * @return void
	 */
	public function test_enable_does_not_fire_enabled_action_on_failure(): void {
		$strategy = $this->makeEmpty( Strategy::class, [
			'enable' => false,
		] );

		$resolver = $this->makeEmpty( Resolver::class, [
			'resolve' => $strategy,
		] );

		$catalog = $this->makeEmpty( Client::class, [
			'get_features' => $this->collection,
		] );

		$manager = new Manager( $catalog, $resolver );

		$enabled_fired = false;

		add_action( 'stellarwp/uplink/feature_enabled', static function () use ( &$enabled_fired ) {
			$enabled_fired = true;
		} );

		$result = $manager->enable( 'test-feature' );

		$this->assertFalse( $result );
		$this->assertFalse( $enabled_fired, 'feature_enabled should not fire when enable fails.' );
	}

	/**
	 * Tests that the feature_disabled action does not fire when the strategy fails to disable.
	 *
	 * @return void
	 */
	public function test_disable_does_not_fire_disabled_action_on_failure(): void {
		$strategy = $this->makeEmpty( Strategy::class, [
			'disable' => false,
		] );

		$resolver = $this->makeEmpty( Resolver::class, [
			'resolve' => $strategy,
		] );

		$catalog = $this->makeEmpty( Client::class, [
			'get_features' => $this->collection,
		] );

		$manager = new Manager( $catalog, $resolver );

		$disabled_fired = false;

		add_action( 'stellarwp/uplink/feature_disabled', static function () use ( &$disabled_fired ) {
			$disabled_fired = true;
		} );

		$result = $manager->disable( 'test-feature' );

		$this->assertFalse( $result );
		$this->assertFalse( $disabled_fired, 'feature_disabled should not fire when disable fails.' );
	}
}
