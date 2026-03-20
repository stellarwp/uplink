<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Licensing\Clients;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use StellarWP\Uplink\Licensing\Clients\Http_Client;
use StellarWP\Uplink\Licensing\Error_Code;
use StellarWP\Uplink\Licensing\Results\Product_Entry;
use StellarWP\Uplink\Licensing\Results\Validation_Result;
use StellarWP\Uplink\Tests\Http\Mock_Client;
use StellarWP\Uplink\Tests\UplinkTestCase;
use WP_Error;

final class Http_ClientTest extends UplinkTestCase {

	private Mock_Client $mock;
	private Psr17Factory $factory;
	private Http_Client $client;

	protected function setUp(): void {
		parent::setUp();

		$this->mock    = new Mock_Client();
		$this->factory = new Psr17Factory();
		$this->client  = new Http_Client( $this->mock, $this->factory, $this->factory, 'https://api.example.com' );
	}

	// -------------------------------------------------------------------------
	// get_products()
	// -------------------------------------------------------------------------

	public function test_get_products_returns_array_on_success(): void {
		$this->mock->add_response( new Response( 200, [], $this->build_products_json() ) );

		$result = $this->client->get_products( 'LWSW-TEST-KEY', 'example.com' );

		$this->assertIsArray( $result );
		$this->assertCount( 2, $result );

		foreach ( $result as $entry ) {
			$this->assertInstanceOf( Product_Entry::class, $entry );
		}
	}

	public function test_get_products_sends_correct_request(): void {
		$this->mock->add_response( new Response( 200, [], $this->build_products_json() ) );

		$this->client->get_products( 'LWSW-TEST-KEY', 'example.com' );

		$request = $this->mock->get_last_request();

		$this->assertSame( 'GET', $request->getMethod() );

		$uri = (string) $request->getUri();

		$this->assertStringContainsString( 'https://api.example.com/stellarwp/v4/products?', $uri );
		$this->assertStringContainsString( 'key=LWSW-TEST-KEY', $uri );
		$this->assertStringContainsString( 'domain=example.com', $uri );
	}

	public function test_get_products_parses_entries_correctly(): void {
		$this->mock->add_response( new Response( 200, [], $this->build_products_json() ) );

		$result = $this->client->get_products( 'LWSW-TEST-KEY', 'example.com' );

		$this->assertSame( 'kadence', $result[0]->get_product_slug() );
		$this->assertSame( 'kadence-pro', $result[0]->get_tier() );
		$this->assertSame( 'active', $result[0]->get_status() );
		$this->assertSame( 3, $result[0]->get_site_limit() );
	}

	public function test_get_products_returns_error_on_http_500(): void {
		$this->mock->add_response( new Response( 500, [], '{}' ) );

		$result = $this->client->get_products( 'LWSW-TEST-KEY', 'example.com' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( Error_Code::UNKNOWN_ERROR, $result->get_error_code() );
	}

	public function test_get_products_returns_error_on_http_404(): void {
		$this->mock->add_response( new Response( 404, [], '{}' ) );

		$result = $this->client->get_products( 'LWSW-TEST-KEY', 'example.com' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( Error_Code::INVALID_KEY, $result->get_error_code() );
	}

	public function test_get_products_returns_error_on_invalid_json(): void {
		$this->mock->add_response( new Response( 200, [], 'not json' ) );

		$result = $this->client->get_products( 'LWSW-TEST-KEY', 'example.com' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( Error_Code::INVALID_RESPONSE, $result->get_error_code() );
	}

	public function test_get_products_returns_error_when_products_key_missing(): void {
		$this->mock->add_response( new Response( 200, [], '{"data": []}' ) );

		$result = $this->client->get_products( 'LWSW-TEST-KEY', 'example.com' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( Error_Code::INVALID_RESPONSE, $result->get_error_code() );
	}

	public function test_get_products_parses_structured_error_response(): void {
		$error_body = (string) wp_json_encode(
			[
				'code'    => Error_Code::EXPIRED,
				'message' => 'License has expired.',
			]
		);

		$this->mock->add_response( new Response( 422, [], $error_body ) );

		$result = $this->client->get_products( 'LWSW-TEST-KEY', 'example.com' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( Error_Code::EXPIRED, $result->get_error_code() );
		$this->assertSame( 'License has expired.', $result->get_error_message() );
	}

	// -------------------------------------------------------------------------
	// validate()
	// -------------------------------------------------------------------------

	public function test_validate_returns_validation_result_on_success(): void {
		$this->mock->add_response( new Response( 200, [], $this->build_validation_json() ) );

		$result = $this->client->validate( 'LWSW-TEST-KEY', 'example.com', 'kadence' );

		$this->assertInstanceOf( Validation_Result::class, $result );
		$this->assertSame( 'valid', $result->get_status() );
		$this->assertTrue( $result->is_valid() );
	}

	public function test_validate_sends_correct_request(): void {
		$this->mock->add_response( new Response( 200, [], $this->build_validation_json() ) );

		$this->client->validate( 'LWSW-TEST-KEY', 'example.com', 'kadence' );

		$request = $this->mock->get_last_request();

		$this->assertSame( 'POST', $request->getMethod() );
		$this->assertSame(
			'https://api.example.com/stellarwp/v4/licenses/validate',
			(string) $request->getUri()
		);
		$this->assertSame( 'application/json', $request->getHeaderLine( 'Content-Type' ) );

		$body = json_decode( (string) $request->getBody(), true );

		$this->assertSame( 'LWSW-TEST-KEY', $body['key'] );
		$this->assertSame( 'example.com', $body['domain'] );
		$this->assertSame( 'kadence', $body['product_slug'] );
	}

	public function test_validate_parses_result_fields(): void {
		$this->mock->add_response( new Response( 200, [], $this->build_validation_json() ) );

		$result = $this->client->validate( 'LWSW-TEST-KEY', 'example.com', 'kadence' );

		$license = $result->get_license();

		$this->assertNotNull( $license );
		$this->assertSame( 'LWSW-TEST-KEY', $license['key'] );

		$subscription = $result->get_subscription();

		$this->assertNotNull( $subscription );
		$this->assertSame( 'kadence', $subscription['product_slug'] );
		$this->assertSame( 'kadence-pro', $subscription['tier'] );

		$activation = $result->get_activation();

		$this->assertNotNull( $activation );
		$this->assertSame( 'example.com', $activation['domain'] );
	}

	public function test_validate_returns_error_on_http_500(): void {
		$this->mock->add_response( new Response( 500, [], '{}' ) );

		$result = $this->client->validate( 'LWSW-TEST-KEY', 'example.com', 'kadence' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( Error_Code::UNKNOWN_ERROR, $result->get_error_code() );
	}

	public function test_validate_returns_error_on_http_404(): void {
		$this->mock->add_response( new Response( 404, [], '{}' ) );

		$result = $this->client->validate( 'LWSW-TEST-KEY', 'example.com', 'kadence' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( Error_Code::INVALID_KEY, $result->get_error_code() );
	}

	public function test_validate_returns_error_on_invalid_json(): void {
		$this->mock->add_response( new Response( 200, [], 'not json' ) );

		$result = $this->client->validate( 'LWSW-TEST-KEY', 'example.com', 'kadence' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( Error_Code::INVALID_RESPONSE, $result->get_error_code() );
	}

	public function test_validate_returns_error_when_status_key_missing(): void {
		$this->mock->add_response( new Response( 200, [], '{"data": {}}' ) );

		$result = $this->client->validate( 'LWSW-TEST-KEY', 'example.com', 'kadence' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( Error_Code::INVALID_RESPONSE, $result->get_error_code() );
	}

	public function test_validate_parses_structured_error_response(): void {
		$error_body = (string) wp_json_encode(
			[
				'code'    => Error_Code::PRODUCT_NOT_FOUND,
				'message' => 'Product not found.',
			]
		);

		$this->mock->add_response( new Response( 422, [], $error_body ) );

		$result = $this->client->validate( 'LWSW-TEST-KEY', 'example.com', 'kadence' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( Error_Code::PRODUCT_NOT_FOUND, $result->get_error_code() );
		$this->assertSame( 'Product not found.', $result->get_error_message() );
	}

	/**
	 * Build a minimal valid products JSON string.
	 *
	 * @return string
	 */
	private function build_products_json(): string {
		return (string) wp_json_encode(
			[
				'products' => [
					[
						'product_slug'      => 'kadence',
						'tier'              => 'kadence-pro',
						'pending_tier'      => null,
						'status'            => 'active',
						'expires'           => '2026-12-31 23:59:59',
						'activations'       => [
							'site_limit'   => 3,
							'active_count' => 1,
							'over_limit'   => false,
						],
						'installed_here'    => true,
						'validation_status' => 'valid',
						'is_valid'          => true,
					],
					[
						'product_slug'      => 'give',
						'tier'              => 'give-pro',
						'pending_tier'      => null,
						'status'            => 'active',
						'expires'           => '2026-12-31 23:59:59',
						'activations'       => [
							'site_limit'   => 3,
							'active_count' => 1,
							'over_limit'   => false,
						],
						'installed_here'    => true,
						'validation_status' => 'valid',
						'is_valid'          => true,
					],
				],
			]
		);
	}

	/**
	 * Build a minimal valid validation JSON string.
	 *
	 * @return string
	 */
	private function build_validation_json(): string {
		return (string) wp_json_encode(
			[
				'status'       => 'valid',
				'license'      => [
					'key'    => 'LWSW-TEST-KEY',
					'status' => 'active',
				],
				'subscription' => [
					'product_slug'    => 'kadence',
					'tier'            => 'kadence-pro',
					'site_limit'      => 3,
					'expiration_date' => '2026-12-31 23:59:59',
					'status'          => 'active',
				],
				'activation'   => [
					'domain'       => 'example.com',
					'activated_at' => '2025-06-01 12:00:00',
				],
			]
		);
	}
}
