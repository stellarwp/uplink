<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests;

use StellarWP\Uplink\Config;
use StellarWP\Uplink\License_Keys;
use StellarWP\Uplink\Register;
use StellarWP\Uplink\Resources\Collection;
use StellarWP\Uplink\Resources\License;

final class License_KeysTest extends UplinkTestCase {

	/**
	 * Tests that an empty slugs array returns an empty collection.
	 *
	 * @return void
	 */
	public function test_it_returns_empty_collection_for_empty_slugs(): void {
		$result = License_Keys::from_slugs( [] );

		$this->assertCount( 0, $result );
	}

	/**
	 * Tests that License objects are collected via the cross-instance filter.
	 *
	 * @return void
	 */
	public function test_it_collects_license_objects_via_cross_instance_filter(): void {
		Register::plugin(
			'collector-test',
			'Collector Test Plugin',
			'1.0.0',
			'uplink/tests/plugin.php',
			Sample_Plugin::class
		);

		$resources = Config::get_container()->get( Collection::class );
		$resources->get( 'collector-test' )->set_license_key( 'license-abc', 'local' );

		$result = License_Keys::from_slugs( [ 'collector-test' ] );

		$this->assertCount( 1, $result );
		$this->assertInstanceOf( License::class, $result->get( 'collector-test' ) );
		$this->assertSame( 'license-abc', $result->get( 'collector-test' )->get_key() );
	}

	/**
	 * Tests that resources without license keys are skipped.
	 *
	 * @return void
	 */
	public function test_it_skips_resources_without_license_keys(): void {
		Register::plugin(
			'no-key-test',
			'No Key Plugin',
			'1.0.0',
			'uplink/tests/plugin.php',
			Sample_Plugin::class
		);

		$result = License_Keys::from_slugs( [ 'no-key-test' ] );

		$this->assertCount( 0, $result );
	}

	/**
	 * Tests that the wp_options fallback finds keys from older instances.
	 *
	 * @return void
	 */
	public function test_it_falls_back_to_wp_options(): void {
		update_option( License::$key_option_prefix . 'legacy-plugin', 'legacy-key-123' );

		$result = License_Keys::from_slugs( [ 'legacy-plugin' ] );

		$this->assertCount( 1, $result );
		$this->assertIsString( $result->get( 'legacy-plugin' ) );
		$this->assertSame( 'legacy-key-123', $result->get( 'legacy-plugin' ) );

		delete_option( License::$key_option_prefix . 'legacy-plugin' );
	}

	/**
	 * Tests that the filter result takes precedence over the wp_options fallback.
	 *
	 * @return void
	 */
	public function test_it_prefers_filter_result_over_option_fallback(): void {
		Register::plugin(
			'priority-test',
			'Priority Test Plugin',
			'1.0.0',
			'uplink/tests/plugin.php',
			Sample_Plugin::class
		);

		$resources = Config::get_container()->get( Collection::class );
		$resources->get( 'priority-test' )->set_license_key( 'filter-key', 'local' );

		// Also set a different value directly in options (simulating stale data).
		update_option( License::$key_option_prefix . 'priority-test', 'option-key' );

		$result = License_Keys::from_slugs( [ 'priority-test' ] );

		// The filter provides a License object, so the option fallback is never reached.
		$this->assertInstanceOf( License::class, $result->get( 'priority-test' ) );
		$this->assertSame( 'filter-key', $result->get( 'priority-test' )->get_key() );
	}

	/**
	 * Tests that unknown slugs not in any instance or options are excluded.
	 *
	 * @return void
	 */
	public function test_it_excludes_unknown_slugs(): void {
		$result = License_Keys::from_slugs( [ 'completely-unknown' ] );

		$this->assertCount( 0, $result );
	}

	/**
	 * Tests that multiple slugs are collected correctly with mixed sources.
	 *
	 * @return void
	 */
	public function test_it_collects_from_mixed_sources(): void {
		Register::plugin(
			'filter-plugin',
			'Filter Plugin',
			'1.0.0',
			'uplink/tests/plugin.php',
			Sample_Plugin::class
		);

		$resources = Config::get_container()->get( Collection::class );
		$resources->get( 'filter-plugin' )->set_license_key( 'from-filter', 'local' );

		update_option( License::$key_option_prefix . 'option-plugin', 'from-option' );

		$result = License_Keys::from_slugs( [ 'filter-plugin', 'option-plugin', 'missing-plugin' ] );

		// Filter source returns a License object.
		$this->assertInstanceOf( License::class, $result->get( 'filter-plugin' ) );
		$this->assertSame( 'from-filter', $result->get( 'filter-plugin' )->get_key() );

		// Legacy wp_options fallback returns a plain string.
		$this->assertIsString( $result->get( 'option-plugin' ) );
		$this->assertSame( 'from-option', $result->get( 'option-plugin' ) );

		// Unknown slug is excluded entirely.
		$this->assertNull( $result->get( 'missing-plugin' ) );

		delete_option( License::$key_option_prefix . 'option-plugin' );
	}
}
