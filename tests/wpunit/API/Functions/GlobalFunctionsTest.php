<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\API\Functions;

use StellarWP\Uplink\Licensing\Repositories\License_Repository;
use StellarWP\Uplink\Licensing\Product_Collection;
use StellarWP\Uplink\Licensing\Results\Product_Entry;
use StellarWP\Uplink\Tests\UplinkTestCase;
use StellarWP\Uplink\Uplink;

/**
 * Tests for the global helper functions defined in global-functions.php.
 *
 * These functions are the public API for StellarWP products to check licensing
 * and feature state. They delegate to version-keyed closures in _stellarwp_uplink_global_function_registry()
 * so that the highest-version Uplink instance's logic always runs.
 *
 * @since 3.0.0
 */
final class GlobalFunctionsTest extends UplinkTestCase {

	protected function setUp(): void {
		parent::setUp();

		delete_option( License_Repository::KEY_OPTION_NAME );
		delete_option( License_Repository::PRODUCTS_STATE_OPTION_NAME );
	}

	protected function tearDown(): void {
		delete_option( License_Repository::KEY_OPTION_NAME );
		delete_option( License_Repository::PRODUCTS_STATE_OPTION_NAME );

		parent::tearDown();
	}

	// -------------------------------------------------------------------------
	// _stellarwp_uplink_global_function_registry()
	// -------------------------------------------------------------------------

	public function test_registry_returns_null_for_unregistered_key(): void {
		$this->assertNull( _stellarwp_uplink_global_function_registry( 'nonexistent_key_global_functions_test' ) );
	}

	public function test_registry_write_returns_null(): void {
		$result = _stellarwp_uplink_global_function_registry( 'test_write_global_functions', '1.0.0', fn() => true );

		$this->assertNull( $result );
	}

	public function test_registry_returns_callable_for_bootstrap_registered_key(): void {
		// Callbacks registered before wp_loaded (by the bootstrap plugin via Uplink::init())
		// should be accessible through the registry.
		$callback = _stellarwp_uplink_global_function_registry( 'stellarwp_uplink_has_unified_license_key' );

		$this->assertNotNull( $callback );
		$this->assertIsCallable( $callback );
	}

	public function test_registry_silently_ignores_writes_after_wp_loaded(): void {
		// Writes after wp_loaded are blocked to prevent late injection.
		// The write returns null (same as a successful write) but the callback is not stored.
		_stellarwp_uplink_global_function_registry( 'test_post_lock_key', Uplink::VERSION, fn() => 'new' );

		$callback = _stellarwp_uplink_global_function_registry( 'test_post_lock_key' );

		$this->assertNull( $callback );
	}

	public function test_registry_returns_null_when_callback_registered_below_leader_version(): void {
		// Register only at a version lower than the leader — the registry
		// resolves to the leader version, which has no callback for this key.
		_stellarwp_uplink_global_function_registry( 'test_lower_version_only', '1.0.0', fn() => 'old' );

		$callback = _stellarwp_uplink_global_function_registry( 'test_lower_version_only' );

		$this->assertNull( $callback );
	}

	// -------------------------------------------------------------------------
	// stellarwp_uplink_has_unified_license_key()
	// -------------------------------------------------------------------------

	public function test_has_unified_license_key_returns_false_without_stored_key(): void {
		$this->assertFalse( stellarwp_uplink_has_unified_license_key() );
	}

	public function test_has_unified_license_key_returns_true_with_stored_key(): void {
		update_option( License_Repository::KEY_OPTION_NAME, 'LWSW-UNIFIED-PRO-2026' );

		$this->assertTrue( stellarwp_uplink_has_unified_license_key() );
	}

	public function test_has_unified_license_key_returns_false_after_key_is_deleted(): void {
		update_option( License_Repository::KEY_OPTION_NAME, 'LWSW-UNIFIED-PRO-2026' );
		delete_option( License_Repository::KEY_OPTION_NAME );

		$this->assertFalse( stellarwp_uplink_has_unified_license_key() );
	}

	// -------------------------------------------------------------------------
	// stellarwp_uplink_is_product_license_active()
	// -------------------------------------------------------------------------

	public function test_is_product_license_active_returns_false_without_cached_products(): void {
		$this->assertFalse( stellarwp_uplink_is_product_license_active( 'give' ) );
	}

	public function test_is_product_license_active_returns_true_for_valid_product(): void {
		$collection = Product_Collection::from_array(
			[
				Product_Entry::from_array(
					[
						'product_slug'      => 'give',
						'tier'              => 'give-pro',
						'status'            => 'active',
						'expires'           => '2030-12-31 23:59:59',
						'validation_status' => 'valid',
					]
				),
			]
		);

		update_option(
			License_Repository::PRODUCTS_STATE_OPTION_NAME,
			[
				'collection'      => $collection->to_array(),
				'last_success_at' => null,
				'last_error'      => null,
			] 
		);

		$this->assertTrue( stellarwp_uplink_is_product_license_active( 'give' ) );
	}

	public function test_is_product_license_active_returns_false_for_invalid_product(): void {
		$collection = Product_Collection::from_array(
			[
				Product_Entry::from_array(
					[
						'product_slug'      => 'give',
						'tier'              => 'give-pro',
						'status'            => 'active',
						'expires'           => '2030-12-31 23:59:59',
						'validation_status' => 'not_activated',
					]
				),
			]
		);

		update_option(
			License_Repository::PRODUCTS_STATE_OPTION_NAME,
			[
				'collection'      => $collection->to_array(),
				'last_success_at' => null,
				'last_error'      => null,
			] 
		);

		$this->assertFalse( stellarwp_uplink_is_product_license_active( 'give' ) );
	}

	public function test_is_product_license_active_returns_false_for_unknown_product(): void {
		$collection = Product_Collection::from_array(
			[
				Product_Entry::from_array(
					[
						'product_slug'      => 'give',
						'tier'              => 'give-pro',
						'status'            => 'active',
						'expires'           => '2030-12-31 23:59:59',
						'validation_status' => 'valid',
					]
				),
			]
		);

		update_option(
			License_Repository::PRODUCTS_STATE_OPTION_NAME,
			[
				'collection'      => $collection->to_array(),
				'last_success_at' => null,
				'last_error'      => null,
			] 
		);

		$this->assertFalse( stellarwp_uplink_is_product_license_active( 'learndash' ) );
	}

	// -------------------------------------------------------------------------
	// stellarwp_uplink_is_feature_enabled() / stellarwp_uplink_is_feature_available()
	// -------------------------------------------------------------------------

	public function test_is_feature_enabled_returns_false_when_no_license_key_stored(): void {
		$this->assertFalse( stellarwp_uplink_is_feature_enabled( 'any-feature' ) );
	}

	public function test_is_feature_available_returns_false_when_no_license_key_stored(): void {
		$this->assertFalse( stellarwp_uplink_is_feature_available( 'any-feature' ) );
	}

	// -------------------------------------------------------------------------
	// stellarwp_uplink_get_unified_license_key()
	// -------------------------------------------------------------------------

	public function test_get_unified_license_key_returns_null_without_stored_key(): void {
		$this->assertNull( stellarwp_uplink_get_unified_license_key() );
	}

	public function test_get_unified_license_key_returns_stored_key(): void {
		update_option( License_Repository::KEY_OPTION_NAME, 'LWSW-UNIFIED-PRO-2026' );

		$this->assertSame( 'LWSW-UNIFIED-PRO-2026', stellarwp_uplink_get_unified_license_key() );
	}

	public function test_get_unified_license_key_returns_null_after_key_is_deleted(): void {
		update_option( License_Repository::KEY_OPTION_NAME, 'LWSW-UNIFIED-PRO-2026' );
		delete_option( License_Repository::KEY_OPTION_NAME );

		$this->assertNull( stellarwp_uplink_get_unified_license_key() );
	}
}
