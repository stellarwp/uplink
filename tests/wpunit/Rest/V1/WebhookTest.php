<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Rest\V1;

use StellarWP\Uplink\Auth\Nonce;
use StellarWP\Uplink\Auth\Token\Contracts\Token_Manager;
use StellarWP\Uplink\Auth\Token\Network_Token_Manager;
use StellarWP\Uplink\Auth\Token\Option_Token_Manager;
use StellarWP\Uplink\Auth\Token\Token_Manager_Factory;
use StellarWP\Uplink\Config;
use StellarWP\Uplink\Rest\Contracts\Authorized;
use StellarWP\Uplink\Tests\RestTestCase;
use StellarWP\Uplink\Uplink;
use WP_Error;
use WP_Http;
use WP_REST_Request;

final class WebhookTest extends RestTestCase {

	/**
	 * @var Token_Manager_Factory
	 */
	private $factory;

	protected function setUp() {
		parent::setUp();

		// Configure the token prefix.
		Config::set_token_auth_prefix( 'kadence_' );

		// Run init again to reload the Token/Rest Providers.
		Uplink::init();

		// Set up our endpoints again.
		do_action( 'rest_api_init' );

		$this->assertSame(
			'kadence_' . Token_Manager::TOKEN_SUFFIX,
			$this->container->get( Config::TOKEN_OPTION_NAME )
		);

		$this->factory = $this->container->get( Token_Manager_Factory::class );
	}

	public function test_token_storage_requires_authorization(): void {
		$request = new WP_REST_Request( 'POST', '/uplink/v1/webhooks/receive-token' );
		$request->set_param( 'token', 'fe3c74d1-0094-4b2a-a8da-c3a730ee71fb' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( WP_Http::UNAUTHORIZED, $response->get_status() );
	}

	public function test_it_throws_validation_error_with_invalid_token_format(): void {
		$token = 'invalid-token-format';
		$nonce = $this->container->get( Nonce::class )->create();

		$request = new WP_REST_Request( 'POST', '/uplink/v1/webhooks/receive-token' );
		$request->set_param( 'token', $token );
		$request->set_header( Authorized::NONCE_HEADER, $nonce );
		$response = $this->server->dispatch( $request );

		$this->assertSame( WP_Http::BAD_REQUEST, $response->get_status() );
		$this->assertSame( 'rest_invalid_param', $response->get_data()['code'] );
	}

	/**
	 * @env singlesite
	 */
	public function test_it_stores_token_with_correct_nonce_on_single_site(): void {
		$this->assertFalse( is_multisite() );
		$token_manager = $this->factory->make();
		$this->assertInstanceOf( Option_Token_Manager::class, $token_manager );
		$token         = 'e12d9e0e-4428-415c-a9d0-3e003f3427c7';
		$nonce         = $this->container->get( Nonce::class )->create();

		$this->assertNull( $token_manager->get() );

		$request = new WP_REST_Request( 'POST', '/uplink/v1/webhooks/receive-token' );
		$request->set_param( 'token', $token );
		$request->set_header( Authorized::NONCE_HEADER, $nonce );
		$response = $this->server->dispatch( $request );

		/** @var array{status: int, message: string} $data */
		$data = $response->get_data();

		$this->assertSame( WP_Http::CREATED, $response->get_status() );
		$this->assertSame( WP_Http::CREATED, $data['status'] );
		$this->assertSame( 'Token stored successfully.', $data['message'] );

		$this->assertSame( $token, $token_manager->get() );
		$this->assertSame( $token, get_option( $token_manager->option_name() ) );
	}

	/**
	 * @env multisite
	 */
	public function test_it_stores_token_with_correct_nonce_on_multi_site_with_custom_domain(): void {
		$this->assertTrue( is_multisite() );

		$sub_site_id = wpmu_create_blog( 'custom.test', '/', 'Test Subsite', 1 );

		$this->assertNotInstanceOf( WP_Error::class, $sub_site_id );
		$this->assertGreaterThan( 1, $sub_site_id );

		switch_to_blog( $sub_site_id );

		$token_manager = $this->factory->make();
		$this->assertInstanceOf( Option_Token_Manager::class, $token_manager );
		$token         = 'e12d9e0e-4428-415c-a9d0-3e003f3427c7';
		$nonce         = $this->container->get( Nonce::class )->create();

		$this->assertNull( $token_manager->get() );

		$request = new WP_REST_Request( 'POST', '/uplink/v1/webhooks/receive-token' );
		$request->set_param( 'token', $token );
		$request->set_header( Authorized::NONCE_HEADER, $nonce );
		$response = $this->server->dispatch( $request );

		/** @var array{status: int, message: string} $data */
		$data = $response->get_data();

		$this->assertSame( WP_Http::CREATED, $response->get_status() );
		$this->assertSame( WP_Http::CREATED, $data['status'] );
		$this->assertSame( 'Token stored successfully.', $data['message'] );

		$this->assertSame( $token, $token_manager->get() );
		$this->assertSame( $token, get_option( $token_manager->option_name() ) );
	}

	/**
	 * @env multisite
	 */
	public function test_it_stores_token_with_correct_nonce_on_multi_site_with_subfolders(): void {
		$this->assertTrue( is_multisite() );

		$sub_site_id = wpmu_create_blog( 'wordpress.test', '/sub1', 'Test Subsite', 1 );

		$this->assertNotInstanceOf( WP_Error::class, $sub_site_id );
		$this->assertGreaterThan( 1, $sub_site_id );

		switch_to_blog( $sub_site_id );

		$token_manager = $this->factory->make();
		$this->assertInstanceOf( Network_Token_Manager::class, $token_manager );
		$token         = 'e12d9e0e-4428-415c-a9d0-3e003f3427c7';
		$nonce         = $this->container->get( Nonce::class )->create();

		$this->assertNull( $token_manager->get() );

		$request = new WP_REST_Request( 'POST', '/uplink/v1/webhooks/receive-token' );
		$request->set_param( 'token', $token );
		$request->set_header( Authorized::NONCE_HEADER, $nonce );
		$response = $this->server->dispatch( $request );

		/** @var array{status: int, message: string} $data */
		$data = $response->get_data();

		$this->assertSame( WP_Http::CREATED, $response->get_status() );
		$this->assertSame( WP_Http::CREATED, $data['status'] );
		$this->assertSame( 'Token stored successfully.', $data['message'] );

		$this->assertSame( $token, $token_manager->get() );
		$this->assertSame( $token, get_network_option( get_current_network_id(), $token_manager->option_name() ) );
	}

}
