<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Rest\V1;

use StellarWP\Uplink\Auth\Nonce;
use StellarWP\Uplink\Auth\Token\Contracts\Token_Manager;
use StellarWP\Uplink\Config;
use StellarWP\Uplink\Rest\Contracts\Authorized;
use StellarWP\Uplink\Tests\RestTestCase;
use StellarWP\Uplink\Uplink;
use WP_Error;
use WP_Http;
use WP_REST_Request;

final class WebhookTest extends RestTestCase {

	/**
	 * @var Token_Manager
	 */
	private $token_manager;

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

		$this->token_manager = $this->container->get( Token_Manager::class );
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
		$token = 'e12d9e0e-4428-415c-a9d0-3e003f3427c7';
		$nonce = $this->container->get( Nonce::class )->create();

		$this->assertNull( $this->token_manager->get() );

		$request = new WP_REST_Request( 'POST', '/uplink/v1/webhooks/receive-token' );
		$request->set_param( 'token', $token );
		$request->set_header( Authorized::NONCE_HEADER, $nonce );
		$response = $this->server->dispatch( $request );

		/** @var array{status: int, message: string} $data */
		$data = $response->get_data();

		$this->assertSame( WP_Http::CREATED, $response->get_status() );
		$this->assertSame( WP_Http::CREATED, $data['status'] );
		$this->assertSame( 'Token stored successfully.', $data['message'] );

		$this->assertSame( $token, $this->token_manager->get() );
		$this->assertSame( $token, get_option( $this->token_manager->option_name() ) );
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

		// Fake the sub-site already had a token ahead of time, before being converted to multisite.
		$old_token = '7df80211-c944-4b94-a99e-6919be3a1d9d';
		$this->assertTrue( update_option( $this->token_manager->option_name(), $old_token, false ) );
		$this->assertNull( $this->token_manager->get() );

		$token = 'fe357794-f50b-44d9-a82f-e48cf5cffeef';
		$nonce = $this->container->get( Nonce::class )->create();

		$this->assertNull( $this->token_manager->get() );

		$request = new WP_REST_Request( 'POST', '/uplink/v1/webhooks/receive-token' );
		$request->set_param( 'token', $token );
		$request->set_header( Authorized::NONCE_HEADER, $nonce );
		$response = $this->server->dispatch( $request );

		/** @var array{status: int, message: string} $data */
		$data = $response->get_data();

		$this->assertSame( WP_Http::CREATED, $response->get_status() );
		$this->assertSame( WP_Http::CREATED, $data['status'] );
		$this->assertSame( 'Token stored successfully.', $data['message'] );

		$this->assertSame( $token, $this->token_manager->get() );
		$this->assertSame( $token, get_network_option( get_current_network_id(), $this->token_manager->option_name() ) );
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

		// Fake the sub-site already had a token ahead of time, before being converted to multisite.
		$old_token = 'dceefe44-1aa1-4870-bcf4-15689fa4a69b';
		$this->assertTrue( update_option( $this->token_manager->option_name(), $old_token, false ) );
		$this->assertNull( $this->token_manager->get() );

		// Create the new token via the webhook.
		$token = '7b734ddd-ff4a-452e-886c-a5bd697283de';
		$nonce = $this->container->get( Nonce::class )->create();

		$request = new WP_REST_Request( 'POST', '/uplink/v1/webhooks/receive-token' );
		$request->set_param( 'token', $token );
		$request->set_header( Authorized::NONCE_HEADER, $nonce );
		$response = $this->server->dispatch( $request );

		/** @var array{status: int, message: string} $data */
		$data = $response->get_data();

		$this->assertSame( WP_Http::CREATED, $response->get_status() );
		$this->assertSame( WP_Http::CREATED, $data['status'] );
		$this->assertSame( 'Token stored successfully.', $data['message'] );

		// Token is now overridden in the network.
		$this->assertSame( $token, $this->token_manager->get() );
		$this->assertSame( $token, get_network_option( get_current_network_id(), $this->token_manager->option_name() ) );
	}

}