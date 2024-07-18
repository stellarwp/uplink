<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Auth\Admin;

use StellarWP\Uplink\Auth\Action_Manager;
use StellarWP\Uplink\Auth\Admin\Connect_Controller;
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

final class ConnectControllerTest extends UplinkTestCase {

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
	}

	public function test_it_stores_basic_token_data(): void {
		global $_GET;

		wp_set_current_user( 1 );

		$this->assertNull( $this->token_manager->get( $this->plugin ) );

		$nonce = ( $this->container->get( Nonce::class ) )->create();
		$token = '53ca40ab-c6c7-4482-a1eb-14c56da31015';

		// Mock these were passed via the query string.
		$_GET[ Connect_Controller::TOKEN ] = $token;
		$_GET[ Connect_Controller::NONCE ] = $nonce;
		$_GET[ Connect_Controller::SLUG ]  = $this->slug;

		// Mock we're an admin inside the dashboard.
		$this->admin_init();

		// Fire off the specification action tied to this slug.
		do_action( $this->container->get( Action_Manager::class )->get_hook_name( $this->plugin ) );

		$this->assertSame( $token, $this->token_manager->get( $this->plugin ) );
	}

	public function test_it_sets_additional_license_key(): void {
		global $_GET;

		wp_set_current_user( 1 );

		$this->assertEmpty( $this->plugin->get_license_key() );

		$this->assertNull( $this->token_manager->get( $this->plugin ) );

		$nonce   = ( $this->container->get( Nonce::class ) )->create();
		$token   = '53ca40ab-c6c7-4482-a1eb-14c56da31015';
		$license = '123456';

		// Mock these were passed via the query string.
		$_GET[ Connect_Controller::TOKEN ]   = $token;
		$_GET[ Connect_Controller::NONCE ]   = $nonce;
		$_GET[ Connect_Controller::LICENSE ] = $license;
		$_GET[ Connect_Controller::SLUG ]    = $this->slug;

		// Mock we're an admin inside the dashboard.
		$this->admin_init();

		// Fire off the specification action tied to this slug.
		do_action( $this->container->get( Action_Manager::class )->get_hook_name( $this->plugin ) );

		$this->assertSame( $token, $this->token_manager->get( $this->plugin ) );
		$this->assertSame( $this->plugin->get_license_key(), $license );
	}

	public function test_it_does_not_store_with_an_invalid_nonce(): void {
		global $_GET;

		wp_set_current_user( 1 );

		$this->assertNull( $this->token_manager->get( $this->plugin ) );

		$token = '53ca40ab-c6c7-4482-a1eb-14c56da31015';

		// Mock these were passed via the query string.
		$_GET[ Connect_Controller::TOKEN ] = $token;
		$_GET[ Connect_Controller::NONCE ] = 'wrong_nonce';
		$_GET[ Connect_Controller::SLUG ]  = $this->slug;

		// Mock we're an admin inside the dashboard.
		$this->admin_init();

		$this->assertNull( $this->token_manager->get( $this->plugin ) );
	}

	public function test_it_does_not_store_an_invalid_token(): void {
		global $_GET;

		wp_set_current_user( 1 );

		$this->assertNull( $this->token_manager->get( $this->plugin ) );

		$nonce = ( $this->container->get( Nonce::class ) )->create();
		$token = 'invalid-token-format';

		// Mock these were passed via the query string.
		$_GET[ Connect_Controller::TOKEN ] = $token;
		$_GET[ Connect_Controller::NONCE ] = $nonce;
		$_GET[ Connect_Controller::SLUG ]  = $this->slug;

		// Mock we're an admin inside the dashboard.
		$this->admin_init();

		// Fire off the specification action tied to this slug.
		do_action( $this->container->get( Action_Manager::class )->get_hook_name( $this->plugin ) );

		$this->assertNull( $this->token_manager->get( $this->plugin ) );
	}

	public function test_it_does_not_stores_token_or_license_with_a_slug_that_does_not_exist(): void {
		global $_GET;

		wp_set_current_user( 1 );

		$this->assertEmpty( $this->plugin->get_license_key() );

		$this->assertNull( $this->token_manager->get( $this->plugin ) );

		$nonce   = ( $this->container->get( Nonce::class ) )->create();
		$token   = '53ca40ab-c6c7-4482-a1eb-14c56da31015';
		$license = '123456';

		// Mock these were passed via the query string.
		$_GET[ Connect_Controller::TOKEN ]   = $token;
		$_GET[ Connect_Controller::NONCE ]   = $nonce;
		$_GET[ Connect_Controller::LICENSE ] = $license;
		$_GET[ Connect_Controller::SLUG ]    = 'a-plugin-slug-that-does-not-exist';

		// Mock we're an admin inside the dashboard.
		$this->admin_init();

		// Fire off the specification action tied to this slug.
		do_action( $this->container->get( Action_Manager::class )->get_hook_name( $this->plugin ) );

		$this->assertNull( $this->token_manager->get( $this->plugin ) );
		$this->assertEmpty( $this->plugin->get_license_key() );
	}

	public function test_it_stores_token_but_not_license_without_a_license(): void {
		global $_GET;

		wp_set_current_user( 1 );

		$this->assertEmpty( $this->plugin->get_license_key() );

		$this->assertNull( $this->token_manager->get( $this->plugin ) );

		$nonce   = ( $this->container->get( Nonce::class ) )->create();
		$token   = '53ca40ab-c6c7-4482-a1eb-14c56da31016';

		// Mock these were passed via the query string.
		$_GET[ Connect_Controller::TOKEN ]   = $token;
		$_GET[ Connect_Controller::NONCE ]   = $nonce;
		$_GET[ Connect_Controller::LICENSE ] = '';
		$_GET[ Connect_Controller::SLUG ]    = $this->slug;

		// Mock we're an admin inside the dashboard.
		$screen = WP_Screen::get( 'dashboard' );
		$GLOBALS['current_screen'] = $screen;

		$this->assertTrue( $screen->in_admin() );

		// Fire off the action the Connect_Controller is running under.
		do_action( 'admin_init' );

		// Fire off the specification action tied to this slug.
		do_action( $this->container->get( Action_Manager::class )->get_hook_name( $this->plugin ) );

		$this->assertSame( $token, $this->token_manager->get( $this->plugin ) );
		$this->assertEmpty( $this->plugin->get_license_key() );
	}

	/**
	 * @env multisite
	 */
	public function test_it_sets_token_and_additional_license_key_on_multisite_network(): void {
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
		$this->assertEmpty( $this->plugin->get_license_key( 'network' ) );

		$this->assertNull( $this->token_manager->get( $this->plugin ) );

		$nonce   = ( $this->container->get( Nonce::class ) )->create();
		$token   = '53ca40ab-c6c7-4482-a1eb-14c56da31015';
		$license = '123456';

		// Mock these were passed via the query string.
		$_GET[ Connect_Controller::TOKEN ]   = $token;
		$_GET[ Connect_Controller::NONCE ]   = $nonce;
		$_GET[ Connect_Controller::LICENSE ] = $license;
		$_GET[ Connect_Controller::SLUG ]    = $this->slug;

		// Mock we're an admin inside the NETWORK dashboard.
		$this->admin_init( true );

		// Fire off the specification action tied to this slug.
		do_action( $this->container->get( Action_Manager::class )->get_hook_name( $this->plugin ) );

		$this->assertSame( $token, $this->token_manager->get( $this->plugin ) );
		$this->assertSame( $this->plugin->get_license_key( 'network' ), $license );
	}

	/**
	 * @env multisite
	 */
	public function test_it_stores_token_data_on_subfolder_subsite(): void {
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

		$this->assertEmpty( $this->plugin->get_license_key( 'network' ) );

		$this->assertNull( $this->token_manager->get( $this->plugin ) );

		$nonce   = ( $this->container->get( Nonce::class ) )->create();
		$token   = '53ca40ab-c6c7-4482-a1eb-14c56da31015';
		$license = '123456';

		// Mock these were passed via the query string.
		$_GET[ Connect_Controller::TOKEN ]   = $token;
		$_GET[ Connect_Controller::NONCE ]   = $nonce;
		$_GET[ Connect_Controller::LICENSE ] = $license;
		$_GET[ Connect_Controller::SLUG ]    = $this->slug;

		// Mock we're in the subsite admin.
		$this->admin_init();

		// Fire off the specification action tied to this slug.
		do_action( $this->container->get( Action_Manager::class )->get_hook_name( $this->plugin ) );

		$this->assertSame( $token, $this->token_manager->get( $this->plugin ) );
		$this->assertSame( $this->plugin->get_license_key( 'network' ), $license );
	}

}
