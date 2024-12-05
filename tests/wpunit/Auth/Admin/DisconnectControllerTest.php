<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Auth\Admin;

use StellarWP\Uplink\Auth\Action_Manager;
use StellarWP\Uplink\Auth\Admin\Disconnect_Controller;
use StellarWP\Uplink\Auth\Nonce;
use StellarWP\Uplink\Auth\Token\Contracts\Token_Manager;
use StellarWP\Uplink\Config;
use StellarWP\Uplink\Register;
use StellarWP\Uplink\Resources\Resource;
use StellarWP\Uplink\Tests\Sample_Plugin;
use StellarWP\Uplink\Tests\UplinkTestCase;
use StellarWP\Uplink\Uplink;
use WP_Error;
use WP_Screen;

final class DisconnectControllerTest extends UplinkTestCase {

	/**
	 * @var Token_Manager
	 */
	private $token_manager;

	/**
	 * The sample plugin slug
	 *
	 * @var string
	 */
	private $slug = 'sample';

	private $token = '53ca40ab-c6c7-4482-a1eb-14c56da31015';

	/**
	 * @var Resource
	 */
	private $plugin;

	protected function setUp(): void {
		parent::setUp();

		// Configure the token prefix.
		Config::set_token_auth_prefix( 'kadence_' );

		// Run init again to reload the Token/Rest Providers.
		Uplink::init();

		$this->assertSame(
			'kadence_' . Token_Manager::TOKEN_SUFFIX,
			$this->container->get( Config::TOKEN_OPTION_NAME )
		);

		$this->token_manager = $this->container->get( Token_Manager::class );

		// Register the sample plugin as a developer would in their plugin.
		$this->plugin = Register::plugin(
			$this->slug,
			'Lib Sample',
			'1.0.10',
			'uplink/index.php',
			Sample_Plugin::class
		);

		// Set a license key.
		$this->plugin->set_license_key( '123456', 'network' );

		// Set a token.
		$this->token_manager->store( $this->token, $this->plugin );
	}

	public function test_it_disconnects_data(): void {
		global $_GET;

		wp_set_current_user( 1 );

		$this->assertEquals( $this->token, $this->token_manager->get( $this->plugin ) );
		$this->assertEquals( '123456', $this->plugin->get_license_key( 'network' ) );

		// Mock these were passed via the query string.
		$_GET[ Disconnect_Controller::ARG ]       = 1;
		$_GET[ Disconnect_Controller::CACHE_KEY ] = 'nada';
		$_GET[ Disconnect_Controller::SLUG ]      = $this->slug;
		$_GET['_wpnonce']                         = wp_create_nonce( Disconnect_Controller::ARG );

		// Mock we're an admin inside the dashboard.
		$this->admin_init();

		// Fire off the specification action tied to this slug.
		do_action( $this->container->get( Action_Manager::class )->get_hook_name( $this->slug ) );

		$this->assertNull( $this->token_manager->get( $this->plugin ) );
		// see how the license is still stored!
		$this->assertEquals( '123456', $this->plugin->get_license_key( 'network' ) );
		$this->assertEquals( 1, did_action( 'stellarwp/uplink/' . Config::get_hook_prefix() . '/' . $this->slug . '/disconnected' ) );
		$this->assertEquals( 1, did_action( 'stellarwp/uplink/' . Config::get_hook_prefix() . '/disconnected' ) );
	}

	public function test_it_does_not_disconnect_with_an_invalid_nonce(): void {
		global $_GET;

		wp_set_current_user( 1 );

		$this->assertEquals( $this->token, $this->token_manager->get( $this->plugin ) );
		$this->assertEquals( '123456', $this->plugin->get_license_key( 'network' ) );

		// Mock these were passed via the query string.
		$_GET[ Disconnect_Controller::ARG ]       = 1;
		$_GET[ Disconnect_Controller::CACHE_KEY ] = 'nada';
		$_GET[ Disconnect_Controller::SLUG ]      = $this->slug;
		$_GET['_wpnonce']                         = 'invalid-nonce';

		// Mock we're an admin inside the dashboard.
		$this->admin_init();

		$this->assertEquals( $this->token, $this->token_manager->get( $this->plugin ) );
		$this->assertEquals( '123456', $this->plugin->get_license_key( 'network' ) );
		$this->assertEmpty( did_action( 'stellarwp/uplink/' . Config::get_hook_prefix() . '/' . $this->slug . '/disconnected' ) );
		$this->assertEmpty( did_action( 'stellarwp/uplink/' . Config::get_hook_prefix() . '/disconnected' ) );
	}

	public function test_it_does_not_disconnect_with_a_slug_that_does_not_exist(): void {
		global $_GET;

		wp_set_current_user( 1 );

		$this->assertEquals( $this->token, $this->token_manager->get( $this->plugin ) );
		$this->assertEquals( '123456', $this->plugin->get_license_key( 'network' ) );

		// Mock these were passed via the query string.
		$_GET[ Disconnect_Controller::ARG ]       = 1;
		$_GET[ Disconnect_Controller::CACHE_KEY ] = 'nada';
		$_GET[ Disconnect_Controller::SLUG ]      = 'a-plugin-slug-that-does-not-exist';
		$_GET['_wpnonce']                         = wp_create_nonce( Disconnect_Controller::ARG );

		// Mock we're an admin inside the dashboard.
		$this->admin_init();

		// Fire off the specification action tied to this slug.
		do_action( $this->container->get( Action_Manager::class )->get_hook_name( $this->slug ) );

		$this->assertEquals( $this->token, $this->token_manager->get( $this->plugin ) );
		$this->assertEquals( '123456', $this->plugin->get_license_key( 'network' ) );
		$this->assertEmpty( did_action( 'stellarwp/uplink/' . Config::get_hook_prefix() . '/a-plugin-slug-that-does-not-exist/disconnected' ) );
	}

	/**
	 * @env multisite
	 */
	public function test_it_disconnects_on_multisite_network(): void {
		global $_GET;

		// Create a subsite, but we won't use it.
		$sub_site_id = wpmu_create_blog( 'wordpress.test', '/sub1', 'Test Subsite', 1 );
		$this->assertNotInstanceOf( WP_Error::class, $sub_site_id );
		$this->assertGreaterThan( 1, $sub_site_id );
		$this->assertTrue( is_multisite() );
		wp_set_current_user( 1 );

		// Mock our sample plugin is network activated, otherwise license key check fails.
		$this->assertTrue( update_site_option( 'active_sitewide_plugins', [
			'uplink/index.php' => time(),
		] ) );
		$this->assertEquals( $this->token, $this->token_manager->get( $this->plugin ) );
		$this->assertEquals( '123456', $this->plugin->get_license_key( 'network' ) );

		// Mock these were passed via the query string.
		$_GET[ Disconnect_Controller::ARG ]       = 1;
		$_GET[ Disconnect_Controller::CACHE_KEY ] = 'nada';
		$_GET[ Disconnect_Controller::SLUG ]      = $this->slug;
		$_GET['_wpnonce']                         = wp_create_nonce( Disconnect_Controller::ARG );

		// Mock we're an admin inside the NETWORK dashboard.
		$this->admin_init( true );

		// Fire off the specification action tied to this slug.
		do_action( $this->container->get( Action_Manager::class )->get_hook_name( $this->slug ) );

		$this->assertNull( $this->token_manager->get( $this->plugin ) );
		$this->assertEquals( '123456', $this->plugin->get_license_key( 'network' ) );
		$this->assertEquals( 1, did_action( 'stellarwp/uplink/' . Config::get_hook_prefix() . '/' . $this->slug . '/disconnected' ) );
		$this->assertEquals( 1, did_action( 'stellarwp/uplink/' . Config::get_hook_prefix() . '/disconnected' ) );
	}

	/**
	 * @env multisite
	 */
	public function test_it_disconnects_on_subfolder_subsite(): void {
		global $_GET;

		// Create a subsite.
		$sub_site_id = wpmu_create_blog( 'wordpress.test', '/sub1', 'Test Subsite', 1 );
		$this->assertNotInstanceOf( WP_Error::class, $sub_site_id );
		$this->assertGreaterThan( 1, $sub_site_id );
		$this->assertTrue( is_multisite() );

		// Use this site, which should not allow a token to be set.
		switch_to_blog( $sub_site_id );
		wp_set_current_user( 1 );

		// Mock our sample plugin is network activated, otherwise license key check fails.
		$this->mock_activate_plugin( 'uplink/index.php', true );

		$this->assertEquals( $this->token, $this->token_manager->get( $this->plugin ) );
		$this->assertEquals( '123456', $this->plugin->get_license_key( 'network' ) );

		// Mock these were passed via the query string.
		$_GET[ Disconnect_Controller::ARG ]       = 1;
		$_GET[ Disconnect_Controller::CACHE_KEY ] = 'nada';
		$_GET[ Disconnect_Controller::SLUG ]      = $this->slug;
		$_GET['_wpnonce']                         = wp_create_nonce( Disconnect_Controller::ARG );

		// Mock we're in the subsite admin.
		$this->admin_init();

		// Fire off the specification action tied to this slug.
		do_action( $this->container->get( Action_Manager::class )->get_hook_name( $this->slug ) );

		$this->assertNull( $this->token_manager->get( $this->plugin ) );
		$this->assertEquals( '123456', $this->plugin->get_license_key( 'network' ) );
		$this->assertEquals( 1, did_action( 'stellarwp/uplink/' . Config::get_hook_prefix() . '/' . $this->slug . '/disconnected' ) );
		$this->assertEquals( 1, did_action( 'stellarwp/uplink/' . Config::get_hook_prefix() . '/disconnected' ) );
	}

}
