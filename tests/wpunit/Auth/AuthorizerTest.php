<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Auth;

use StellarWP\Uplink\Auth\Authorizer;
use StellarWP\Uplink\Config;
use StellarWP\Uplink\Tests\UplinkTestCase;
use StellarWP\Uplink\Uplink;
use WP_Error;

final class AuthorizerTest extends UplinkTestCase {

	/**
	 * @var Authorizer
	 */
	private $authorizer;

	protected function setUp(): void {
		parent::setUp();

		Config::set_token_auth_prefix( 'kadence_' );

		// Run init again to reload all providers.
		Uplink::init();

		$this->authorizer = $this->container->get( Authorizer::class );
	}

	public function test_it_does_not_authorize_for_logged_out_users(): void {
		$this->assertFalse( is_user_logged_in() );
		$this->assertFalse( $this->authorizer->can_auth() );
	}

	public function test_it_authorizes_a_single_site(): void {
		wp_set_current_user( 1 );

		$this->assertTrue( is_user_logged_in() );
		$this->assertTrue( is_super_admin() );
		$this->assertTrue( $this->authorizer->can_auth() );
	}

	public function test_it_does_not_authorize_a_non_super_admin_on_single_site(): void {
		$id = wp_create_user( 'tester', 'tester', 'tester@wordpress.test' );

		wp_set_current_user( $id );

		$this->assertTrue( is_user_logged_in() );
		$this->assertFalse( is_super_admin() );

		$this->assertFalse( $this->authorizer->can_auth() );
	}

	/**
	 * @env multisite
	 */
	public function test_it_does_not_authorize_a_non_super_admin_on_multisite(): void {
		$this->assertTrue( is_multisite() );

		// Main test domain is wordpress.test, create a subfolder sub-site.
		$sub_site_id = wpmu_create_blog( 'wordpress.test', '/sub1', 'Test Subsite', 1 );

		$this->assertNotInstanceOf( WP_Error::class, $sub_site_id );
		$this->assertGreaterThan( 1, $sub_site_id );

		switch_to_blog( $sub_site_id );

		$id = wp_create_user( 'tester', 'tester', 'tester@wordpress.test' );

		wp_set_current_user( $id );

		$this->assertTrue( is_user_logged_in() );
		$this->assertFalse( is_super_admin() );

		$this->assertFalse( $this->authorizer->can_auth() );
	}

	/**
	 * @env multisite
	 */
	public function test_it_authorizes_a_subsite_with_multisite_subfolders(): void {
		$this->assertTrue( is_multisite() );

		// Main test domain is wordpress.test, create a subfolder sub-site.
		$sub_site_id = wpmu_create_blog( 'wordpress.test', '/sub1', 'Test Subsite', 1 );

		$this->assertNotInstanceOf( WP_Error::class, $sub_site_id );
		$this->assertGreaterThan( 1, $sub_site_id );

		switch_to_blog( $sub_site_id );

		wp_set_current_user( 1 );
		$this->assertTrue( is_user_logged_in() );
		$this->assertTrue( is_super_admin() );

		$this->assertTrue( $this->authorizer->can_auth() );
	}

	/**
	 * @env multisite
	 */
	public function test_it_authorizes_a_subsite(): void {
		$this->mock_activate_plugin( 'uplink/index.php', true );
		$this->assertTrue( is_multisite() );

		// Main test domain is wordpress.test, create a completely custom domain.
		$sub_site_id = wpmu_create_blog( 'custom.test', '/', 'Test Subsite', 1 );

		$this->assertNotInstanceOf( WP_Error::class, $sub_site_id );
		$this->assertGreaterThan( 1, $sub_site_id );

		switch_to_blog( $sub_site_id );

		wp_set_current_user( 1 );
		$this->assertTrue( is_user_logged_in() );
		$this->assertTrue( is_super_admin() );

		$this->assertTrue( $this->authorizer->can_auth() );
	}
}
