<?php declare( strict_types=1 );

namespace wpunit\Utils;

use StellarWP\Uplink\Tests\UplinkTestCase;
use StellarWP\Uplink\Uplink;
use StellarWP\Uplink\Utils\Version;

/**
 * @since 3.0.0
 */
class VersionTest extends UplinkTestCase {

	/**
	 * @test
	 *
	 * @since 3.0.0
	 */
	public function it_should_be_highest_when_no_higher_version_exists(): void {
		$this->assertTrue( Version::is_highest() );
	}

	/**
	 * @test
	 *
	 * @since 3.0.0
	 */
	public function it_should_not_be_highest_when_a_higher_version_exists(): void {
		add_filter( 'stellarwp/uplink/highest_version', static function () {
			return '99.0.0';
		} );

		$this->assertFalse( Version::is_highest() );
	}

	/**
	 * @test
	 *
	 * @since 3.0.0
	 */
	public function it_should_be_highest_when_versions_are_equal(): void {
		add_filter( 'stellarwp/uplink/highest_version', static function () {
			return Uplink::VERSION;
		} );

		$this->assertTrue( Version::is_highest() );
	}

	/**
	 * @test
	 *
	 * @since 3.0.0
	 */
	public function it_should_handle_when_it_has_the_highest_version(): void {
		$this->assertTrue( Version::should_handle( 'test_action' ) );
	}

	/**
	 * @test
	 *
	 * @since 3.0.0
	 */
	public function it_should_not_handle_when_a_higher_version_exists(): void {
		add_filter( 'stellarwp/uplink/highest_version', static function () {
			return '99.0.0';
		} );

		$this->assertFalse( Version::should_handle( 'test_action' ) );
	}

	/**
	 * @test
	 *
	 * @since 3.0.0
	 */
	public function it_should_not_handle_when_action_already_claimed(): void {
		do_action( 'stellarwp/uplink/handled/test_action' );

		$this->assertFalse( Version::should_handle( 'test_action' ) );
	}

	/**
	 * @test
	 *
	 * @since 3.0.0
	 */
	public function it_should_fire_the_hook_on_success(): void {
		$this->assertSame( 0, did_action( 'stellarwp/uplink/handled/test_action' ) );

		Version::should_handle( 'test_action' );

		$this->assertSame( 1, did_action( 'stellarwp/uplink/handled/test_action' ) );
	}

	/**
	 * @test
	 *
	 * @since 3.0.0
	 */
	public function it_should_not_fire_the_hook_on_failure(): void {
		add_filter( 'stellarwp/uplink/highest_version', static function () {
			return '99.0.0';
		} );

		Version::should_handle( 'test_action' );

		$this->assertSame( 0, did_action( 'stellarwp/uplink/handled/test_action' ) );
	}

	/**
	 * @test
	 *
	 * @since 3.0.0
	 */
	public function it_should_handle_different_actions_independently(): void {
		$this->assertTrue( Version::should_handle( 'admin_page' ) );
		$this->assertTrue( Version::should_handle( 'rest_routes' ) );
	}

	/**
	 * @test
	 *
	 * @since 3.0.0
	 */
	public function it_should_only_handle_an_action_once(): void {
		$this->assertTrue( Version::should_handle( 'admin_page' ) );
		$this->assertFalse( Version::should_handle( 'admin_page' ) );
	}
}
