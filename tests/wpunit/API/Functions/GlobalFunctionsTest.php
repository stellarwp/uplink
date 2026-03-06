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
 * and feature state. They delegate to version-keyed closures in _uplink_global_function_registry()
 * so that the highest-version Uplink instance's logic always runs.
 *
 * @since 3.0.0
 */
final class GlobalFunctionsTest extends UplinkTestCase {

	protected function setUp(): void {
		parent::setUp();

		delete_option( License_Repository::KEY_OPTION_NAME );
		delete_transient( License_Repository::PRODUCTS_TRANSIENT_KEY );
	}

	protected function tearDown(): void {
		delete_option( License_Repository::KEY_OPTION_NAME );
		delete_transient( License_Repository::PRODUCTS_TRANSIENT_KEY );

		parent::tearDown();
	}

	// -------------------------------------------------------------------------
	// _uplink_global_function_registry()
	// -------------------------------------------------------------------------

	public function test_registry_returns_null_for_unregistered_key(): void {
		$this->assertNull( _uplink_global_function_registry( 'nonexistent_key_global_functions_test' ) );
	}

	public function test_registry_write_returns_null(): void {
		$result = _uplink_global_function_registry( 'test_write_global_functions', '1.0.0', fn() => true );

		$this->assertNull( $result );
	}

	public function test_registry_retrieves_callback_registered_at_leader_version(): void {
		$called = false;

		_uplink_global_function_registry(
			'test_leader_registration',
			Uplink::VERSION,
			static function () use ( &$called ): bool {
				$called = true;

				return true;
			}
		);

		$callback = _uplink_global_function_registry( 'test_leader_registration' );

		$this->assertNotNull( $callback );
		$this->assertTrue( $callback() );
		$this->assertTrue( $called );
	}

	public function test_registry_uses_highest_version_callback_when_multiple_registered(): void {
		_uplink_global_function_registry( 'test_versioned_fn', '1.0.0', fn() => 'low' );
		_uplink_global_function_registry( 'test_versioned_fn', Uplink::VERSION, fn() => 'high' );

		$callback = _uplink_global_function_registry( 'test_versioned_fn' );

		$this->assertNotNull( $callback );
		$this->assertSame( 'high', $callback() );
	}

	public function test_registry_returns_null_when_callback_registered_below_leader_version(): void {
		// Register only at a version lower than the leader — the registry
		// resolves to the leader version, which has no callback for this key.
		_uplink_global_function_registry( 'test_lower_version_only', '1.0.0', fn() => 'old' );

		$callback = _uplink_global_function_registry( 'test_lower_version_only' );

		$this->assertNull( $callback );
	}

	// -------------------------------------------------------------------------
	// uplink_has_unified_license_key()
	// -------------------------------------------------------------------------

	public function test_has_unified_license_key_returns_false_without_stored_key(): void {
		$this->assertFalse( uplink_has_unified_license_key() );
	}

	public function test_has_unified_license_key_returns_true_with_stored_key(): void {
		update_option( License_Repository::KEY_OPTION_NAME, 'LWSW-UNIFIED-PRO-2026' );

		$this->assertTrue( uplink_has_unified_license_key() );
	}

	public function test_has_unified_license_key_returns_false_after_key_is_deleted(): void {
		update_option( License_Repository::KEY_OPTION_NAME, 'LWSW-UNIFIED-PRO-2026' );
		delete_option( License_Repository::KEY_OPTION_NAME );

		$this->assertFalse( uplink_has_unified_license_key() );
	}

	// -------------------------------------------------------------------------
	// uplink_is_product_license_active()
	// -------------------------------------------------------------------------

	public function test_is_product_license_active_returns_false_without_cached_products(): void {
		$this->assertFalse( uplink_is_product_license_active( 'give' ) );
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

		set_transient( License_Repository::PRODUCTS_TRANSIENT_KEY, $collection->to_array() );

		$this->assertTrue( uplink_is_product_license_active( 'give' ) );
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

		set_transient( License_Repository::PRODUCTS_TRANSIENT_KEY, $collection->to_array() );

		$this->assertFalse( uplink_is_product_license_active( 'give' ) );
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

		set_transient( License_Repository::PRODUCTS_TRANSIENT_KEY, $collection->to_array() );

		$this->assertFalse( uplink_is_product_license_active( 'learndash' ) );
	}

	// -------------------------------------------------------------------------
	// uplink_is_feature_enabled() / uplink_is_feature_available()
	// -------------------------------------------------------------------------

	public function test_is_feature_enabled_returns_wp_error_when_no_license_key_stored(): void {
		// Feature_Repository cannot fetch the catalog without a license key,
		// so Manager converts the failure into a WP_Error.
		$result = uplink_is_feature_enabled( 'any-feature' );

		$this->assertInstanceOf( \WP_Error::class, $result );
	}

	public function test_is_feature_available_returns_wp_error_when_no_license_key_stored(): void {
		$result = uplink_is_feature_available( 'any-feature' );

		$this->assertInstanceOf( \WP_Error::class, $result );
	}
}
