<?php declare( strict_types=1 );

namespace wpunit;

use StellarWP\Uplink\Config;
use StellarWP\Uplink\Register;
use StellarWP\Uplink\Resources\Collection;
use StellarWP\Uplink\Resources\License;
use StellarWP\Uplink\Tests\UplinkTestCase;
use StellarWP\Uplink\Uplink;
use StellarWP\Uplink\Tests\Sample_Plugin;

class CrossInstanceHooksTest extends UplinkTestCase {

	protected function setUp(): void {
		parent::setUp();

		Register::plugin(
			'cross-test',
			'Cross Test Plugin',
			'1.0.0',
			'uplink/tests/plugin.php',
			Sample_Plugin::class
		);
	}

	/**
	 * @test
	 */
	public function it_should_report_version_via_highest_version_filter(): void {
		$highest = apply_filters( 'stellarwp/uplink/highest_version', '0.0.0' );

		$this->assertSame( Uplink::VERSION, $highest );
	}

	/**
	 * @test
	 */
	public function it_should_not_downgrade_highest_version(): void {
		$highest = apply_filters( 'stellarwp/uplink/highest_version', '99.0.0' );

		$this->assertSame( '99.0.0', $highest );
	}

	/**
	 * @test
	 */
	public function it_should_set_license_key_via_cross_instance_filter(): void {
		$result = apply_filters( 'stellarwp/uplink/set_license_key', false, 'cross-test', 'abc123', 'local' );

		$this->assertTrue( $result );

		$stored = get_option( 'stellarwp_uplink_license_key_cross-test', '' );
		$this->assertSame( 'abc123', $stored );
	}

	/**
	 * @test
	 */
	public function it_should_not_set_license_key_for_unknown_slug(): void {
		$result = apply_filters( 'stellarwp/uplink/set_license_key', false, 'nonexistent-plugin', 'abc123', 'local' );

		$this->assertFalse( $result );
	}

	/**
	 * @test
	 */
	public function it_should_delete_license_key_via_cross_instance_filter(): void {
		$collection = Config::get_container()->get( Collection::class );
		$resource   = $collection->get( 'cross-test' );
		$resource->set_license_key( 'to-be-deleted', 'local' );

		$this->assertNotEmpty( get_option( 'stellarwp_uplink_license_key_cross-test', '' ) );

		$result = apply_filters( 'stellarwp/uplink/delete_license_key', false, 'cross-test', 'local' );

		$this->assertTrue( $result );
		$this->assertEmpty( get_option( 'stellarwp_uplink_license_key_cross-test', '' ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_delete_license_key_for_unknown_slug(): void {
		$result = apply_filters( 'stellarwp/uplink/delete_license_key', false, 'nonexistent-plugin', 'local' );

		$this->assertFalse( $result );
	}

	/**
	 * Tests that license objects are gathered for requested slugs via the filter.
	 *
	 * @return void
	 */
	public function test_it_gathers_license_objects_for_requested_slugs(): void {
		$collection = Config::get_container()->get( Collection::class );
		$collection->get( 'cross-test' )->set_license_key( 'gather-key-123', 'local' );

		/** @var array<string, License> $licenses */
		$licenses = apply_filters( 'stellarwp/uplink/licenses', [], [ 'cross-test' ] );

		$this->assertArrayHasKey( 'cross-test', $licenses );
		$this->assertInstanceOf( License::class, $licenses['cross-test'] );
		$this->assertSame( 'gather-key-123', $licenses['cross-test']->get_key() );
	}

	/**
	 * Tests that already-gathered slugs are not overwritten by the filter.
	 *
	 * @return void
	 */
	public function test_it_skips_slugs_already_gathered(): void {
		$collection = Config::get_container()->get( Collection::class );
		$resource   = $collection->get( 'cross-test' );
		$resource->set_license_key( 'new-key', 'local' );

		$pre_populated = [ 'cross-test' => $resource->get_license_object() ];

		/** @var array<string, License> $licenses */
		$licenses = apply_filters( 'stellarwp/uplink/licenses', $pre_populated, [ 'cross-test' ] );

		$this->assertSame( $pre_populated['cross-test'], $licenses['cross-test'] );
	}

	/**
	 * Tests that unknown slugs return an empty array from the licenses filter.
	 *
	 * @return void
	 */
	public function test_it_returns_empty_for_unknown_slugs(): void {
		/** @var array<string, License> $licenses */
		$licenses = apply_filters( 'stellarwp/uplink/licenses', [], [ 'nonexistent-plugin' ] );

		$this->assertSame( [], $licenses );
	}
}
