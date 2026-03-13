<?php declare( strict_types=1 );

namespace wpunit;

use StellarWP\Uplink\Tests\UplinkTestCase;

/**
 * Tests for _stellarwp_uplink_instance_registry().
 *
 * These tests only cover observable behavior from within the test environment,
 * where wp_loaded has already fired. Registration and reset require the
 * production bootstrap window (before wp_loaded) and are covered by integration tests.
 *
 * @since 3.0.0
 */
class InstanceRegistryTest extends UplinkTestCase {

	public function test_it_returns_an_array(): void {
		// @phpstan-ignore function.internal
		$this->assertIsArray( _stellarwp_uplink_instance_registry() );
	}

	public function test_it_silently_ignores_registrations_after_wp_loaded(): void {
		$unique_version = '99.99.99';

		// @phpstan-ignore function.internal
		_stellarwp_uplink_instance_registry( $unique_version );

		// @phpstan-ignore function.internal
		$versions = _stellarwp_uplink_instance_registry();

		$this->assertArrayNotHasKey( $unique_version, $versions );
	}

	public function test_it_ignores_empty_version_string(): void {
		// @phpstan-ignore function.internal
		$before = _stellarwp_uplink_instance_registry();

		// @phpstan-ignore function.internal
		_stellarwp_uplink_instance_registry( '' );

		// @phpstan-ignore function.internal
		$after = _stellarwp_uplink_instance_registry();

		$this->assertSame( $before, $after );
	}
}
