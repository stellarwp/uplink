<?php declare( strict_types=1 );

namespace wpunit\Utils;

use StellarWP\Uplink\Tests\UplinkTestCase;
use StellarWP\Uplink\Uplink;
use StellarWP\Uplink\Utils\Version;

class VersionTest extends UplinkTestCase {

	/**
	 * @test
	 */
	public function it_should_be_leader_when_it_has_the_highest_version(): void {
		$this->assertTrue( Version::is_leader( 'test_responsibility' ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_be_leader_when_a_higher_version_exists(): void {
		add_filter( 'stellarwp/uplink/highest_version', static function () {
			return '99.0.0';
		} );

		$this->assertFalse( Version::is_leader( 'test_responsibility' ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_be_leader_when_responsibility_already_claimed(): void {
		do_action( 'stellarwp/uplink/leader/test_responsibility' );

		$this->assertFalse( Version::is_leader( 'test_responsibility' ) );
	}

	/**
	 * @test
	 */
	public function it_should_claim_the_responsibility_on_success(): void {
		$this->assertSame( 0, did_action( 'stellarwp/uplink/leader/test_responsibility' ) );

		Version::is_leader( 'test_responsibility' );

		$this->assertSame( 1, did_action( 'stellarwp/uplink/leader/test_responsibility' ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_claim_the_responsibility_on_failure(): void {
		add_filter( 'stellarwp/uplink/highest_version', static function () {
			return '99.0.0';
		} );

		Version::is_leader( 'test_responsibility' );

		$this->assertSame( 0, did_action( 'stellarwp/uplink/leader/test_responsibility' ) );
	}

	/**
	 * @test
	 */
	public function it_should_allow_different_keys_independently(): void {
		$this->assertTrue( Version::is_leader( 'admin_page' ) );
		$this->assertTrue( Version::is_leader( 'rest_routes' ) );
	}

	/**
	 * @test
	 */
	public function it_should_only_grant_leadership_once_per_key(): void {
		$this->assertTrue( Version::is_leader( 'admin_page' ) );
		$this->assertFalse( Version::is_leader( 'admin_page' ) );
	}

	/**
	 * @test
	 */
	public function it_should_be_leader_when_versions_are_equal(): void {
		add_filter( 'stellarwp/uplink/highest_version', static function () {
			return Uplink::VERSION;
		} );

		$this->assertTrue( Version::is_leader( 'test_responsibility' ) );
	}
}
