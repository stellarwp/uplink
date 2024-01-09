<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Auth\Admin;

use stdClass;
use StellarWP\Uplink\API\Client;
use StellarWP\Uplink\API\Validation_Response;
use StellarWP\Uplink\Auth\Admin\Connect_Controller;
use StellarWP\Uplink\Auth\Nonce;
use StellarWP\Uplink\Auth\Token\Contracts\Token_Manager;
use StellarWP\Uplink\Auth\Token\Token_Factory;
use StellarWP\Uplink\Config;
use StellarWP\Uplink\Register;
use StellarWP\Uplink\Resources\Collection;
use StellarWP\Uplink\Tests\Sample_Plugin;
use StellarWP\Uplink\Tests\UplinkTestCase;
use StellarWP\Uplink\Uplink;
use WP_Error;
use WP_Screen;

final class ConnectControllerTest extends UplinkTestCase {

	/**
	 * @var Token_Factory
	 */
	private $token_manager_factory;

	/**
	 * The sample plugin slug
	 *
	 * @var string
	 */
	private $slug = 'sample';

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

		$this->token_manager_factory = $this->container->get( Token_Factory::class );

		// Register the sample plugin as a developer would in their plugin.
		Register::plugin(
			$this->slug,
			'Lib Sample',
			'1.0.10',
			'uplink/index.php',
			Sample_Plugin::class
		);
	}

	protected function tearDown(): void {
		$GLOBALS['current_screen'] = null;

		parent::tearDown();
	}

	public function test_it_stores_basic_token_data(): void {
		global $_GET;

		wp_set_current_user( 1 );

		$plugin        = $this->container->get( Collection::class )->offsetGet( $this->slug );
		$token_manager = $this->token_manager_factory->make( $plugin );

		$this->assertNull( $token_manager->get() );

		$nonce = ( $this->container->get( Nonce::class ) )->create();
		$token = '53ca40ab-c6c7-4482-a1eb-14c56da31015';

		// Mock these were passed via the query string.
		$_GET[ Connect_Controller::TOKEN ] = $token;
		$_GET[ Connect_Controller::NONCE ] = $nonce;
		$_GET[ Connect_Controller::SLUG ]  = $this->slug;

		// Mock we're an admin inside the dashboard.
		$screen = WP_Screen::get( 'dashboard' );
		$GLOBALS['current_screen'] = $screen;

		$this->assertTrue( $screen->in_admin() );

		// Fire off the admin_init action to register our admin_action_$hook_prefix_$slug actions.
		do_action( 'admin_init' );

		// Fire off the specification action tied to this slug.
		do_action( sprintf( 'admin_action_%s_%s', Config::get_hook_prefix_underscored(), $this->slug ) );

		$this->assertSame( $token, $token_manager->get() );
	}

	public function test_it_sets_additional_license_key(): void {
		global $_GET;

		$plugin = $this->container->get( Collection::class )->offsetGet( $this->slug );

		$clientMock = $this->makeEmpty( Client::class, [
			'validate_license' => static function () use ( $plugin ) {
				$response = new stdClass();
				$response->api_upgrade = 0;
				$response->api_expired = 0;

				return new Validation_Response( '123456', is_multisite() ? 'network' : 'local', $response, $plugin );
			},
		] );

		$this->container->bind( Client::class, $clientMock );

		wp_set_current_user( 1 );

		$this->assertEmpty( $plugin->get_license_key() );

		$token_manager = $this->token_manager_factory->make( $plugin );

		$this->assertNull( $token_manager->get() );

		$nonce   = ( $this->container->get( Nonce::class ) )->create();
		$token   = '53ca40ab-c6c7-4482-a1eb-14c56da31015';
		$license = '123456';

		// Mock these were passed via the query string.
		$_GET[ Connect_Controller::TOKEN ]   = $token;
		$_GET[ Connect_Controller::NONCE ]   = $nonce;
		$_GET[ Connect_Controller::LICENSE ] = $license;
		$_GET[ Connect_Controller::SLUG ]    = $this->slug;

		// Mock we're an admin inside the dashboard.
		$screen = WP_Screen::get( 'dashboard' );
		$GLOBALS['current_screen'] = $screen;

		$this->assertTrue( $screen->in_admin() );

		// Fire off the admin_init action to register our admin_action_$hook_prefix_$slug actions.
		do_action( 'admin_init' );

		// Fire off the specification action tied to this slug.
		do_action( sprintf( 'admin_action_%s_%s', Config::get_hook_prefix_underscored(), $this->slug ) );

		$this->assertSame( $token, $token_manager->get() );
		$this->assertSame( $plugin->get_license_key( is_multisite() ? 'network' : 'local' ), $license );
	}

	public function test_it_does_not_store_with_an_invalid_nonce(): void {
		global $_GET;

		wp_set_current_user( 1 );

		$plugin        = $this->container->get( Collection::class )->offsetGet( $this->slug );
		$token_manager = $this->token_manager_factory->make( $plugin );

		$this->assertNull( $token_manager->get() );

		$token = '53ca40ab-c6c7-4482-a1eb-14c56da31015';

		// Mock these were passed via the query string.
		$_GET[ Connect_Controller::TOKEN ] = $token;
		$_GET[ Connect_Controller::NONCE ] = 'wrong_nonce';
		$_GET[ Connect_Controller::SLUG ]  = $this->slug;

		// Mock we're an admin inside the dashboard.
		$screen = WP_Screen::get( 'dashboard' );
		$GLOBALS['current_screen'] = $screen;

		$this->assertTrue( $screen->in_admin() );

		// Fire off the admin_init action to register our admin_action_$hook_prefix_$slug actions.
		do_action( 'admin_init' );

		// Fire off the specification action tied to this slug.
		do_action( sprintf( 'admin_action_%s_%s', Config::get_hook_prefix_underscored(), $this->slug ) );

		$this->assertNull( $token_manager->get() );
	}

	public function test_it_does_not_store_an_invalid_token(): void {
		global $_GET;

		wp_set_current_user( 1 );

		$plugin        = $this->container->get( Collection::class )->offsetGet( $this->slug );
		$token_manager = $this->token_manager_factory->make( $plugin );

		$this->assertNull( $token_manager->get() );

		$nonce = ( $this->container->get( Nonce::class ) )->create();
		$token = 'invalid-token-format';

		// Mock these were passed via the query string.
		$_GET[ Connect_Controller::TOKEN ] = $token;
		$_GET[ Connect_Controller::NONCE ] = $nonce;
		$_GET[ Connect_Controller::SLUG ]  = $this->slug;

		// Mock we're an admin inside the dashboard.
		$screen = WP_Screen::get( 'dashboard' );
		$GLOBALS['current_screen'] = $screen;

		$this->assertTrue( $screen->in_admin() );

		// Fire off the admin_init action to register our admin_action_$hook_prefix_$slug actions.
		do_action( 'admin_init' );

		// Fire off the specification action tied to this slug.
		do_action( sprintf( 'admin_action_%s_%s', Config::get_hook_prefix_underscored(), $this->slug ) );

		$this->assertNull( $token_manager->get() );
	}

	public function test_it_does_not_store_a_token_without_a_slug(): void {
		global $_GET;

		wp_set_current_user( 1 );

		$plugin        = $this->container->get( Collection::class )->offsetGet( $this->slug );
		$token_manager = $this->token_manager_factory->make( $plugin );

		$this->assertNull( $token_manager->get() );

		$nonce = ( $this->container->get( Nonce::class ) )->create();
		$token = '53ca40ab-c6c7-4482-a1eb-14c56da31015';

		// Mock these were passed via the query string.
		$_GET[ Connect_Controller::TOKEN ] = $token;
		$_GET[ Connect_Controller::NONCE ] = $nonce;

		// Mock we're an admin inside the dashboard.
		$screen = WP_Screen::get( 'dashboard' );
		$GLOBALS['current_screen'] = $screen;

		$this->assertTrue( $screen->in_admin() );

		// Fire off the admin_init action to register our admin_action_$hook_prefix_$slug actions.
		do_action( 'admin_init' );

		// Fire off the specification action tied to this slug.
		do_action( sprintf( 'admin_action_%s_%s', Config::get_hook_prefix_underscored(), $this->slug ) );

		$this->assertNull( $token_manager->get() );
	}

	public function test_it_stores_token_but_not_license_without_a_license(): void {
		global $_GET;

		wp_set_current_user( 1 );

		$plugin = $this->container->get( Collection::class )->offsetGet( $this->slug );
		$this->assertEmpty( $plugin->get_license_key() );

		$token_manager = $this->token_manager_factory->make( $plugin );

		$this->assertNull( $token_manager->get() );

		$nonce   = ( $this->container->get( Nonce::class ) )->create();
		$token   = '53ca40ab-c6c7-4482-a1eb-14c56da31015';

		// Mock these were passed via the query string.
		$_GET[ Connect_Controller::TOKEN ]   = $token;
		$_GET[ Connect_Controller::NONCE ]   = $nonce;
		$_GET[ Connect_Controller::LICENSE ] = '';
		$_GET[ Connect_Controller::SLUG ]    = $this->slug;

		// Mock we're an admin inside the dashboard.
		$screen = WP_Screen::get( 'dashboard' );
		$GLOBALS['current_screen'] = $screen;

		$this->assertTrue( $screen->in_admin() );

		// Fire off the admin_init action to register our admin_action_$hook_prefix_$slug actions.
		do_action( 'admin_init' );

		// Fire off the specification action tied to this slug.
		do_action( sprintf( 'admin_action_%s_%s', Config::get_hook_prefix_underscored(), $this->slug ) );

		$this->assertSame( $token, $token_manager->get() );
		$this->assertEmpty( $plugin->get_license_key( is_multisite() ? 'network' : 'local' ) );
	}

	public function test_it_stores_token_but_not_license_without_a_valid_license(): void {
		global $_GET;

		$plugin = $this->container->get( Collection::class )->offsetGet( $this->slug );

		$clientMock = $this->makeEmpty( Client::class, [
			'validate_license' => static function () use ( $plugin ) {
				$response = new stdClass();
				$response->api_upgrade = 0;
				$response->api_expired = 1; // makes validation fail.

				return new Validation_Response( '123456', is_multisite() ? 'network' : 'local', $response, $plugin );
			},
		] );

		$this->container->bind( Client::class, $clientMock );

		wp_set_current_user( 1 );

		$this->assertEmpty( $plugin->get_license_key() );

		$token_manager = $this->token_manager_factory->make( $plugin );

		$this->assertNull( $token_manager->get() );

		$nonce   = ( $this->container->get( Nonce::class ) )->create();
		$token   = '53ca40ab-c6c7-4482-a1eb-14c56da31015';

		// Mock these were passed via the query string.
		$_GET[ Connect_Controller::TOKEN ]   = $token;
		$_GET[ Connect_Controller::NONCE ]   = $nonce;
		$_GET[ Connect_Controller::LICENSE ] = '123456';
		$_GET[ Connect_Controller::SLUG ]    = $this->slug;

		// Mock we're an admin inside the dashboard.
		$screen = WP_Screen::get( 'dashboard' );
		$GLOBALS['current_screen'] = $screen;

		$this->assertTrue( $screen->in_admin() );

		// Fire off the admin_init action to register our admin_action_$hook_prefix_$slug actions.
		do_action( 'admin_init' );

		// Fire off the specification action tied to this slug.
		do_action( sprintf( 'admin_action_%s_%s', Config::get_hook_prefix_underscored(), $this->slug ) );

		$this->assertSame( $token, $token_manager->get() );
		$this->assertEmpty( $plugin->get_license_key( is_multisite() ? 'network' : 'local' ) );
	}

	/**
	 * @env multisite
	 */
	public function test_it_sets_token_and_additional_license_key_on_multisite_network(): void {
		global $_GET;

		$plugin = $this->container->get( Collection::class )->offsetGet( $this->slug );

		$clientMock = $this->makeEmpty( Client::class, [
			'validate_license' => static function () use ( $plugin ) {
				$response = new stdClass();
				$response->api_upgrade = 0;
				$response->api_expired = 0;

				return new Validation_Response( '123456', 'network', $response, $plugin );
			},
		] );

		$this->container->bind( Client::class, $clientMock );

		// Create a subsite, but we won't use it.
		$sub_site_id = wpmu_create_blog( 'wordpress.test', '/sub1', 'Test Subsite', 1 );
		$this->assertNotInstanceOf( WP_Error::class, $sub_site_id );
		$this->assertGreaterThan( 1, $sub_site_id );
		$this->assertTrue( is_multisite() );
		wp_set_current_user( 1 );

		// Mock our sample plugin is network activated, otherwise license key check fails.
		$this->mock_activate_plugin( 'uplink/index.php', true );

		$this->assertEmpty( $plugin->get_license_key( 'network' ) );

		$token_manager = $this->token_manager_factory->make( $plugin );

		$this->assertNull( $token_manager->get() );

		$nonce   = ( $this->container->get( Nonce::class ) )->create();
		$token   = '53ca40ab-c6c7-4482-a1eb-14c56da31015';
		$license = '123456';

		// Mock these were passed via the query string.
		$_GET[ Connect_Controller::TOKEN ]   = $token;
		$_GET[ Connect_Controller::NONCE ]   = $nonce;
		$_GET[ Connect_Controller::LICENSE ] = $license;
		$_GET[ Connect_Controller::SLUG ]    = $this->slug;

		// Mock we're an admin inside the NETWORK dashboard.
		$screen = WP_Screen::get( 'dashboard-network' );
		$GLOBALS['current_screen'] = $screen;

		$this->assertTrue( $screen->in_admin( 'network' ) );
		$this->assertTrue( $screen->in_admin() );

		// Fire off the admin_init action to register our admin_action_$hook_prefix_$slug actions.
		do_action( 'admin_init' );

		// Fire off the specification action tied to this slug.
		do_action( sprintf( 'admin_action_%s_%s', Config::get_hook_prefix_underscored(), $this->slug ) );

		$this->assertSame( $token, $token_manager->get() );
		$this->assertSame( $plugin->get_license_key( 'network' ), $license );
	}

	/**
	 * @env multisite
	 */
	public function test_it_stores_token_data_on_subfolder_subsite(): void {
		global $_GET;

		$plugin = $this->container->get( Collection::class )->offsetGet( $this->slug );

		$clientMock = $this->makeEmpty( Client::class, [
			'validate_license' => static function () use ( $plugin ) {
				$response = new stdClass();
				$response->api_upgrade = 0;
				$response->api_expired = 0;

				return new Validation_Response( '123456', 'network', $response, $plugin );
			},
		] );

		$this->container->bind( Client::class, $clientMock );

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

		$this->assertEmpty( $plugin->get_license_key( 'network' ) );

		$token_manager = $this->token_manager_factory->make( $plugin );

		$this->assertNull( $token_manager->get() );

		$nonce   = ( $this->container->get( Nonce::class ) )->create();
		$token   = '53ca40ab-c6c7-4482-a1eb-14c56da31015';
		$license = '123456';

		// Mock these were passed via the query string.
		$_GET[ Connect_Controller::TOKEN ]   = $token;
		$_GET[ Connect_Controller::NONCE ]   = $nonce;
		$_GET[ Connect_Controller::LICENSE ] = $license;
		$_GET[ Connect_Controller::SLUG ]    = $this->slug;

		// Mock we're in the subsite admin.
		$screen = WP_Screen::get( 'dashboard' );
		$GLOBALS['current_screen'] = $screen;

		$this->assertFalse( $screen->in_admin( 'network' ) );
		$this->assertTrue( $screen->in_admin() );

		// Fire off the admin_init action to register our admin_action_$hook_prefix_$slug actions.
		do_action( 'admin_init' );

		// Fire off the specification action tied to this slug.
		do_action( sprintf( 'admin_action_%s_%s', Config::get_hook_prefix_underscored(), $this->slug ) );

		$this->assertSame( $token, $token_manager->get() );
		$this->assertSame( $plugin->get_license_key( 'network' ), $license );
	}

}
