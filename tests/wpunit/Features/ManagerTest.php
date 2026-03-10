<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Features;

use StellarWP\Uplink\Catalog\Catalog_Repository;
use StellarWP\Uplink\Features\Error_Code;
use StellarWP\Uplink\Features\Feature_Collection;
use StellarWP\Uplink\Features\Feature_Repository;
use StellarWP\Uplink\Features\Contracts\Strategy;
use StellarWP\Uplink\Features\Manager;
use StellarWP\Uplink\Features\Strategy\Strategy_Factory;
use StellarWP\Uplink\Features\Types\Feature;
use StellarWP\Uplink\Features\Types\Flag;
use StellarWP\Uplink\Features\Types\Plugin;
use StellarWP\Uplink\Licensing\Repositories\License_Repository;
use StellarWP\Uplink\Tests\UplinkTestCase;
use WP_Error;

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
	 * @var Feature_Collection
	 */
	private Feature_Collection $collection;

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

		if ( ! defined( 'STELLARWP_UPLINK_FEATURES_USE_FIXTURE_DATA' ) ) {
			define( 'STELLARWP_UPLINK_FEATURES_USE_FIXTURE_DATA', true );
		}

		delete_transient( 'stellarwp_uplink_feature_catalog' );

		$this->collection = new Feature_Collection();
		$this->collection->add( Flag::from_array( [
			'slug'         => 'test-feature',
			'group'        => 'TestGroup',
			'tier'         => 'Tier 1',
			'name'         => 'Test Feature',
			'is_available' => true,
		] ) );

		$repository = $this->makeEmpty(
			Feature_Repository::class,
			[
				'get' => $this->collection,
			]
		);

		$this->mock_strategy = $this->makeEmpty(
			Strategy::class,
			[
				'enable'    => true,
				'disable'   => true,
				'is_active' => true,
			]
		);

		$factory = $this->makeEmpty(
			Strategy_Factory::class,
			[
				'make' => $this->mock_strategy,
			]
		);

		$this->manager = new Manager( $repository, $factory, 'test-key', 'example.com' );
	}

	/**
	 * Cleans up integration-test state after each test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		delete_option( 'stellarwp_uplink_feature_kad-pattern-hub_active' );
		delete_option( License_Repository::KEY_OPTION_NAME );
		delete_transient( Feature_Repository::TRANSIENT_KEY );
		delete_transient( Catalog_Repository::TRANSIENT_KEY );
		delete_transient( License_Repository::PRODUCTS_TRANSIENT_KEY );

		parent::tearDown();
	}

	/**
	 * Tests a known feature can be enabled successfully.
	 *
	 * @return void
	 */
	public function test_it_enables_a_feature(): void {
		$result = $this->manager->enable( 'test-feature' );

		$this->assertInstanceOf( Feature::class, $result );
	}

	/**
	 * Tests enabling an unknown feature returns a WP_Error.
	 *
	 * @return void
	 */
	public function test_it_returns_wp_error_when_enabling_unknown_feature(): void {
		$result = $this->manager->enable( 'nonexistent' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( Error_Code::FEATURE_NOT_FOUND, $result->get_error_code() );
	}

	/**
	 * Tests a known feature can be disabled successfully.
	 *
	 * @return void
	 */
	public function test_it_disables_a_feature(): void {
		$result = $this->manager->disable( 'test-feature' );

		$this->assertInstanceOf( Feature::class, $result );
	}

	/**
	 * Tests disabling an unknown feature returns a WP_Error.
	 *
	 * @return void
	 */
	public function test_it_returns_wp_error_when_disabling_unknown_feature(): void {
		$result = $this->manager->disable( 'nonexistent' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( Error_Code::FEATURE_NOT_FOUND, $result->get_error_code() );
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
	 * Tests is_enabled returns WP_Error for a feature not in the catalog.
	 *
	 * @return void
	 */
	public function test_is_enabled_returns_wp_error_for_unknown_feature(): void {
		$result = $this->manager->is_enabled( 'nonexistent' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( Error_Code::FEATURE_NOT_FOUND, $result->get_error_code() );
	}

	/**
	 * Tests get_features returns the catalog collection.
	 *
	 * @return void
	 */
	public function test_get_features_returns_collection(): void {
		$features = $this->manager->get_all();

		$this->assertInstanceOf( Feature_Collection::class, $features );
		$this->assertSame( 1, $features->count() );
	}

	/**
	 * Tests get_feature resolves typed features from the catalog.
	 *
	 * @return void
	 */
	public function test_get_feature_resolves_typed_features_from_catalog(): void {
		update_option( License_Repository::KEY_OPTION_NAME, 'lwsw-unified-kad-pro-2026' );

		$manager = $this->container->get( Manager::class );

		$flag = $manager->get( 'kad-pattern-hub' );
		$this->assertInstanceOf( Flag::class, $flag );
		$this->assertSame( 'kad-pattern-hub', $flag->get_slug() );

		$plugin = $manager->get( 'kad-blocks-pro' );
		$this->assertInstanceOf( Plugin::class, $plugin );
		$this->assertSame( 'kad-blocks-pro', $plugin->get_slug() );
	}

	/**
	 * Tests enable and disable write and clear the DB flag.
	 *
	 * @return void
	 */
	public function test_enable_and_disable_write_db_flags(): void {
		update_option( License_Repository::KEY_OPTION_NAME, 'lwsw-unified-kad-pro-2026' );

		$manager    = $this->container->get( Manager::class );
		$option_key = 'stellarwp_uplink_feature_kad-pattern-hub_active';

		// Enable — DB flag set, returned feature and is_enabled agree.
		$enabled = $manager->enable( 'kad-pattern-hub' );
		$this->assertInstanceOf( Feature::class, $enabled );
		$this->assertTrue( $enabled->is_enabled() );
		$this->assertSame( '1', get_option( $option_key ) );
		$this->assertTrue( $manager->is_enabled( 'kad-pattern-hub' ) );

		// Disable — DB flag cleared, returned feature and is_enabled agree.
		$disabled = $manager->disable( 'kad-pattern-hub' );
		$this->assertInstanceOf( Feature::class, $disabled );
		$this->assertFalse( $disabled->is_enabled() );
		$this->assertSame( '0', get_option( $option_key ) );
		$this->assertFalse( $manager->is_enabled( 'kad-pattern-hub' ) );
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

		add_action(
			'stellarwp/uplink/feature_enabling',
			static function () use ( &$enabling_fired ) {
				$enabling_fired = true;
			}
		);

		add_action(
			'stellarwp/uplink/feature_enabled',
			static function () use ( &$enabled_fired ) {
				$enabled_fired = true;
			}
		);

		add_action(
			'stellarwp/uplink/test-feature/feature_enabling',
			static function () use ( &$slug_enabling_fired ) {
				$slug_enabling_fired = true;
			}
		);

		add_action(
			'stellarwp/uplink/test-feature/feature_enabled',
			static function () use ( &$slug_enabled_fired ) {
				$slug_enabled_fired = true;
			}
		);

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

		add_action(
			'stellarwp/uplink/feature_disabling',
			static function () use ( &$disabling_fired ) {
				$disabling_fired = true;
			}
		);

		add_action(
			'stellarwp/uplink/feature_disabled',
			static function () use ( &$disabled_fired ) {
				$disabled_fired = true;
			}
		);

		add_action(
			'stellarwp/uplink/test-feature/feature_disabling',
			static function () use ( &$slug_disabling_fired ) {
				$slug_disabling_fired = true;
			}
		);

		add_action(
			'stellarwp/uplink/test-feature/feature_disabled',
			static function () use ( &$slug_disabled_fired ) {
				$slug_disabled_fired = true;
			}
		);

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
		$error = new WP_Error( 'enable_failed', 'Could not enable feature.' );

		$strategy = $this->makeEmpty(
			Strategy::class,
			[
				'enable' => $error,
			]
		);

		$factory = $this->makeEmpty(
			Strategy_Factory::class,
			[
				'make' => $strategy,
			]
		);

		$repository = $this->makeEmpty(
			Feature_Repository::class,
			[
				'get' => $this->collection,
			]
		);

		$manager = new Manager( $repository, $factory, 'test-key', 'example.com' );

		$enabled_fired = false;

		add_action(
			'stellarwp/uplink/feature_enabled',
			static function () use ( &$enabled_fired ) {
				$enabled_fired = true;
			}
		);

		$result = $manager->enable( 'test-feature' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertFalse( $enabled_fired, 'feature_enabled should not fire when enable fails.' );
	}

	/**
	 * Tests that the feature_disabled action does not fire when the strategy fails to disable.
	 *
	 * @return void
	 */
	public function test_disable_does_not_fire_disabled_action_on_failure(): void {
		$error = new WP_Error( 'disable_failed', 'Could not disable feature.' );

		$strategy = $this->makeEmpty(
			Strategy::class,
			[
				'disable' => $error,
			]
		);

		$factory = $this->makeEmpty(
			Strategy_Factory::class,
			[
				'make' => $strategy,
			]
		);

		$repository = $this->makeEmpty(
			Feature_Repository::class,
			[
				'get' => $this->collection,
			]
		);

		$manager = new Manager( $repository, $factory, 'test-key', 'example.com' );

		$disabled_fired = false;

		add_action(
			'stellarwp/uplink/feature_disabled',
			static function () use ( &$disabled_fired ) {
				$disabled_fired = true;
			}
		);

		$result = $manager->disable( 'test-feature' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertFalse( $disabled_fired, 'feature_disabled should not fire when disable fails.' );
	}

	/**
	 * Tests that get_features returns a WP_Error when the catalog errors.
	 *
	 * @return void
	 */
	public function test_get_features_returns_wp_error_when_catalog_errors(): void {
		$error = new WP_Error( 'api_error', 'Could not fetch features.' );

		$repository = $this->makeEmpty(
			Feature_Repository::class,
			[
				'get' => $error,
			]
		);

		$factory = $this->makeEmpty( Strategy_Factory::class );

		$manager = new Manager( $repository, $factory, 'test-key', 'example.com' );

		$this->assertInstanceOf( WP_Error::class, $manager->get_all() );
	}

	/**
	 * Tests that is_enabled returns WP_Error when the catalog returns a WP_Error.
	 *
	 * @return void
	 */
	public function test_is_enabled_returns_wp_error_when_catalog_errors(): void {
		$error = new WP_Error( 'api_error', 'Could not fetch features.' );

		$repository = $this->makeEmpty(
			Feature_Repository::class,
			[
				'get' => $error,
			]
		);

		$factory = $this->makeEmpty( Strategy_Factory::class );

		$manager = new Manager( $repository, $factory, 'test-key', 'example.com' );

		$result = $manager->is_enabled( 'test-feature' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'api_error', $result->get_error_code() );
	}

	/**
	 * Tests is_available returns true for a feature with is_available set.
	 *
	 * @return void
	 */
	public function test_is_available_returns_true_for_available_feature(): void {
		$this->assertTrue( $this->manager->is_available( 'test-feature' ) );
	}

	/**
	 * Tests is_available returns false for a feature with is_available unset.
	 *
	 * @return void
	 */
	public function test_is_available_returns_false_for_unavailable_feature(): void {
		$collection = new Feature_Collection();
		$collection->add( Flag::from_array( [
			'slug'         => 'locked-feature',
			'group'        => 'TestGroup',
			'tier'         => 'Pro',
			'name'         => 'Locked Feature',
			'is_available' => false,
		] ) );

		$strategy = $this->makeEmpty(
			Strategy::class,
			[
				'is_active' => false,
			]
		);

		$factory = $this->makeEmpty(
			Strategy_Factory::class,
			[
				'make' => $strategy,
			]
		);

		$repository = $this->makeEmpty(
			Feature_Repository::class,
			[
				'get' => $collection,
			]
		);

		$manager = new Manager( $repository, $factory, 'test-key', 'example.com' );

		$this->assertFalse( $manager->is_available( 'locked-feature' ) );
	}

	/**
	 * Tests is_available returns WP_Error for an unknown feature.
	 *
	 * @return void
	 */
	public function test_is_available_returns_wp_error_for_unknown_feature(): void {
		$result = $this->manager->is_available( 'nonexistent' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( Error_Code::FEATURE_NOT_FOUND, $result->get_error_code() );
	}

	/**
	 * Tests is_available returns WP_Error when the catalog errors.
	 *
	 * @return void
	 */
	public function test_is_available_returns_wp_error_when_catalog_errors(): void {
		$error = new WP_Error( 'api_error', 'Could not fetch features.' );

		$repository = $this->makeEmpty(
			Feature_Repository::class,
			[
				'get' => $error,
			]
		);

		$factory = $this->makeEmpty( Strategy_Factory::class );

		$manager = new Manager( $repository, $factory, 'test-key', 'example.com' );

		$this->assertInstanceOf( WP_Error::class, $manager->is_available( 'test-feature' ) );
	}

	/**
	 * Tests that exists returns true for a feature in the catalog.
	 *
	 * @return void
	 */
	public function test_exists_returns_true_for_catalog_feature(): void {
		$this->assertTrue( $this->manager->exists( 'test-feature' ) );
	}

	/**
	 * Tests that exists returns false for a feature not in the catalog.
	 *
	 * @return void
	 */
	public function test_exists_returns_false_for_unknown_feature(): void {
		$this->assertFalse( $this->manager->exists( 'nonexistent' ) );
	}

	/**
	 * Tests that exists returns WP_Error when the catalog errors.
	 *
	 * @return void
	 */
	public function test_exists_returns_wp_error_when_catalog_errors(): void {
		$error = new WP_Error( 'api_error', 'Could not fetch features.' );

		$repository = $this->makeEmpty(
			Feature_Repository::class,
			[
				'get' => $error,
			]
		);

		$factory = $this->makeEmpty( Strategy_Factory::class );

		$manager = new Manager( $repository, $factory, 'test-key', 'example.com' );

		$this->assertInstanceOf( WP_Error::class, $manager->exists( 'test-feature' ) );
	}

	/**
	 * Tests that get returns null when the catalog errors.
	 *
	 * @return void
	 */
	public function test_get_returns_null_when_catalog_errors(): void {
		$error = new WP_Error( 'api_error', 'Could not fetch features.' );

		$repository = $this->makeEmpty(
			Feature_Repository::class,
			[
				'get' => $error,
			]
		);

		$factory = $this->makeEmpty( Strategy_Factory::class );

		$manager = new Manager( $repository, $factory, 'test-key', 'example.com' );

		$this->assertNull( $manager->get( 'test-feature' ) );
	}

	/**
	 * Tests that enable returns a Feature with is_enabled stamped.
	 *
	 * @return void
	 */
	public function test_enable_returns_feature_with_enabled_state(): void {
		$result = $this->manager->enable( 'test-feature' );

		$this->assertInstanceOf( Feature::class, $result );
		$this->assertTrue( $result->is_enabled() );
	}

	/**
	 * Tests that disable returns a Feature with is_enabled stamped.
	 *
	 * @return void
	 */
	public function test_disable_returns_feature_with_disabled_state(): void {
		$inactive_strategy = $this->makeEmpty(
			Strategy::class,
			[
				'disable'   => true,
				'is_active' => false,
			]
		);

		$factory = $this->makeEmpty(
			Strategy_Factory::class,
			[
				'make' => $inactive_strategy,
			]
		);

		$repository = $this->makeEmpty(
			Feature_Repository::class,
			[
				'get' => $this->collection,
			]
		);

		$manager = new Manager( $repository, $factory, 'test-key', 'example.com' );

		$result = $manager->disable( 'test-feature' );

		$this->assertInstanceOf( Feature::class, $result );
		$this->assertFalse( $result->is_enabled() );
	}

	/**
	 * Tests that enable returns a WP_Error when the catalog errors.
	 *
	 * @return void
	 */
	public function test_enable_returns_wp_error_when_catalog_errors(): void {
		$error = new WP_Error( 'api_error', 'Could not fetch features.' );

		$repository = $this->makeEmpty(
			Feature_Repository::class,
			[
				'get' => $error,
			]
		);

		$factory = $this->makeEmpty( Strategy_Factory::class );

		$manager = new Manager( $repository, $factory, 'test-key', 'example.com' );

		$result = $manager->enable( 'test-feature' );

		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * Tests that disable returns a WP_Error when the catalog errors.
	 *
	 * @return void
	 */
	public function test_disable_returns_wp_error_when_catalog_errors(): void {
		$error = new WP_Error( 'api_error', 'Could not fetch features.' );

		$repository = $this->makeEmpty(
			Feature_Repository::class,
			[
				'get' => $error,
			]
		);

		$factory = $this->makeEmpty( Strategy_Factory::class );

		$manager = new Manager( $repository, $factory, 'test-key', 'example.com' );

		$result = $manager->disable( 'test-feature' );

		$this->assertInstanceOf( WP_Error::class, $result );
	}
}
