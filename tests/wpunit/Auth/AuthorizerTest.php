<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Auth;

use StellarWP\Uplink\Auth\Authorizer;
use StellarWP\Uplink\Auth\Token\Token_Manager_Factory;
use StellarWP\Uplink\Config;
use StellarWP\Uplink\Register;
use StellarWP\Uplink\Resources\Collection;
use StellarWP\Uplink\Resources\Resource;
use StellarWP\Uplink\Tests\Sample_Plugin;
use StellarWP\Uplink\Tests\UplinkTestCase;
use StellarWP\Uplink\Uplink;
use WP_Error;

final class AuthorizerTest extends UplinkTestCase {

	/**
	 * @var Authorizer
	 */
	private $authorizer;

	/**
	 * @var Resource
	 */
	private $plugin;

	protected function setUp(): void {
		parent::setUp();

		Config::set_token_auth_prefix( 'kadence_' );

		// Run init again to reload all providers.
		Uplink::init();

		$slug = 'sample';

		// Register the sample plugin as a developer would in their plugin.
		Register::plugin(
			$slug,
			'Lib Sample',
			'1.0.10',
			'uplink/index.php',
			Sample_Plugin::class
		);

		$this->plugin     = $this->container->get( Collection::class )->offsetGet( $slug );
		$this->authorizer = $this->container->get( Authorizer::class );
	}

	public function test_it_does_not_authorize_for_logged_out_users(): void {
		$this->mock_activate_plugin( 'uplink/index.php' );
		$this->assertFalse( is_user_logged_in() );
		$this->assertFalse( $this->authorizer->can_auth( $this->plugin ) );
	}

	public function test_it_authorizes_a_single_site(): void {
		$this->mock_activate_plugin( 'uplink/index.php' );

		wp_set_current_user( 1 );

		$this->assertTrue( is_user_logged_in() );
		$this->assertTrue( is_super_admin() );
		$this->assertTrue( $this->authorizer->can_auth( $this->plugin ) );
	}

	/**
	 * @env multisite
	 */
	public function test_it_authorizes_a_subsite_with_multisite_subfolders_enabled_and_is_not_network_activated(): void {
		$this->assertTrue( is_multisite() );

		// Main test domain is wordpress.test, create a subfolder sub-site.
		$sub_site_id = wpmu_create_blog( 'wordpress.test', '/sub1', 'Test Subsite', 1 );

		$this->assertNotInstanceOf( WP_Error::class, $sub_site_id );
		$this->assertGreaterThan( 1, $sub_site_id );

		switch_to_blog( $sub_site_id );

		// Activate the plugin on subsite only.
		$this->mock_activate_plugin( 'uplink/index.php' );

		wp_set_current_user( 1 );
		$this->assertTrue( is_user_logged_in() );
		$this->assertTrue( is_super_admin() );

		$this->assertTrue( $this->authorizer->can_auth( $this->plugin ) );
	}

	/**
	 * @env multisite
	 */
	public function test_it_does_not_authorize_a_subsite_with_multisite_subfolders_enabled_and_is_network_activated(): void {
		$this->mock_activate_plugin( 'uplink/index.php', true );
		$this->assertTrue( is_multisite() );

		// Main test domain is wordpress.test, create a subfolder sub-site.
		$sub_site_id = wpmu_create_blog( 'wordpress.test', '/sub1', 'Test Subsite', 1 );

		$this->assertNotInstanceOf( WP_Error::class, $sub_site_id );
		$this->assertGreaterThan( 1, $sub_site_id );

		switch_to_blog( $sub_site_id );

		wp_set_current_user( 1 );
		$this->assertTrue( is_user_logged_in() );
		$this->assertTrue( is_super_admin() );

		$this->assertFalse( $this->authorizer->can_auth( $this->plugin ) );
	}

	/**
	 * @env multisite
	 */
	public function test_it_does_not_authorize_a_subsite_with_an_existing_network_token(): void {
		$this->mock_activate_plugin( 'uplink/index.php', true );
		$this->assertTrue( is_multisite() );

		$token_manager = $this->container->get( Token_Manager_Factory::class )->make( true );
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

		$this->assertFalse( $this->authorizer->can_auth( $this->plugin ) );
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

		$this->assertTrue( $this->authorizer->can_auth( $this->plugin ) );
	}

}
