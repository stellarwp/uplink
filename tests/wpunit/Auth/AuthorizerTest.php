<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Auth;

use StellarWP\Uplink\Auth\Authorizer;
use StellarWP\Uplink\Auth\Token\Contracts\Token_Manager;
use StellarWP\Uplink\Config;
use StellarWP\Uplink\Tests\UplinkTestCase;
use StellarWP\Uplink\Uplink;
use WP_Error;

final class AuthorizerTest extends UplinkTestCase {

	/**
	 * @var Authorizer
	 */
	private $authorizer;

	protected function setUp() {
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

	/**
	 * @env multisite
	 */
	public function test_it_does_not_authorize_a_subsite_with_multisite_subfolders_enabled(): void {
		$this->assertTrue( is_multisite() );

		// Main test domain is wordpress.test, create a subfolder sub-site.
		$sub_site_id = wpmu_create_blog( 'wordpress.test', '/sub1', 'Test Subsite', 1 );

		$this->assertNotInstanceOf( WP_Error::class, $sub_site_id );
		$this->assertGreaterThan( 1, $sub_site_id );

		switch_to_blog( $sub_site_id );

		wp_set_current_user( 1 );
		$this->assertTrue( is_user_logged_in() );
		$this->assertTrue( is_super_admin() );

		$this->assertFalse( $this->authorizer->can_auth() );
	}

	/**
	 * @env multisite
	 */
	public function test_it_does_not_authorize_a_subsite_with_an_existing_network_token(): void {
		$this->assertTrue( is_multisite() );

		$token_manager = $this->container->get( Token_Manager::class );
		$token         = '695be4b3-ad6e-4863-9287-3052f597b1f6';

		// Store a token while on the main site.
		$this->assertTrue( $token_manager->store( '695be4b3-ad6e-4863-9287-3052f597b1f6' ) );

		$this->assertSame( $token, get_network_option( get_current_network_id(), $token_manager->option_name() ) );
		$this->assertEmpty( get_option( $token_manager->option_name() ) );

		// Main test domain is wordpress.test, create a sub domain.
		$sub_site_id = wpmu_create_blog( 'sub1.wordpress.test', '/', 'Test Subsite', 1 );

		$this->assertNotInstanceOf( WP_Error::class, $sub_site_id );
		$this->assertGreaterThan( 1, $sub_site_id );

		switch_to_blog( $sub_site_id );

		wp_set_current_user( 1 );
		$this->assertTrue( is_user_logged_in() );
		$this->assertTrue( is_super_admin() );

		$this->assertFalse( $this->authorizer->can_auth() );
	}

	/**
	 * @env multisite
	 */
	public function test_it_authorizes_a_subsite(): void {
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
