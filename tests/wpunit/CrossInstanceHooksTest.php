<?php declare( strict_types=1 );

namespace wpunit;

use StellarWP\Uplink\Config;
use StellarWP\Uplink\Register;
use StellarWP\Uplink\Resources\Collection;
use StellarWP\Uplink\Tests\UplinkTestCase;
use StellarWP\Uplink\Uplink;

class CrossInstanceHooksTest extends UplinkTestCase {

	protected function setUp(): void {
		parent::setUp();

		Register::plugin(
			'cross-test',
			'Cross Test Plugin',
			'1.0.0',
			'cross-test/cross-test.php',
			Uplink::class
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
}
