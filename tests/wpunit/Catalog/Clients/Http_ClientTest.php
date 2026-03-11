<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Catalog\Clients;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use StellarWP\Uplink\Catalog\Catalog_Collection;
use StellarWP\Uplink\Catalog\Clients\Http_Client;
use StellarWP\Uplink\Catalog\Error_Code;
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
		$this->client  = new Http_Client( $this->mock, $this->factory, 'https://api.example.com' );
	}

	public function test_get_catalog_returns_collection_on_success(): void {
		$body = $this->build_catalog_json();

		$this->mock->add_response( new Response( 200, [], $body ) );

		$result = $this->client->get_catalog();

		$this->assertInstanceOf( Catalog_Collection::class, $result );
		$this->assertCount( 2, $result );
		$this->assertNotNull( $result->get( 'kadence' ) );
		$this->assertNotNull( $result->get( 'give' ) );
	}

	public function test_get_catalog_sends_correct_request(): void {
		$this->mock->add_response( new Response( 200, [], $this->build_catalog_json() ) );

		$this->client->get_catalog();

		$request = $this->mock->get_last_request();

		$this->assertSame( 'GET', $request->getMethod() );
		$this->assertSame(
			'https://api.example.com/stellarwp/v4/catalog',
			(string) $request->getUri()
		);
	}

	public function test_get_catalog_returns_error_on_http_500(): void {
		$this->mock->add_response( new Response( 500, [], 'Internal Server Error' ) );

		$result = $this->client->get_catalog();

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( Error_Code::INVALID_RESPONSE, $result->get_error_code() );
		$this->assertStringContainsString( '500', $result->get_error_message() );
	}

	public function test_get_catalog_returns_error_on_http_404(): void {
		$this->mock->add_response( new Response( 404, [], 'Not Found' ) );

		$result = $this->client->get_catalog();

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( Error_Code::INVALID_RESPONSE, $result->get_error_code() );
	}

	public function test_get_catalog_returns_error_on_invalid_json(): void {
		$this->mock->add_response( new Response( 200, [], 'not json' ) );

		$result = $this->client->get_catalog();

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( Error_Code::INVALID_RESPONSE, $result->get_error_code() );
	}

	public function test_get_catalog_returns_error_on_empty_array(): void {
		$this->mock->add_response( new Response( 200, [], '[]' ) );

		$result = $this->client->get_catalog();

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( Error_Code::INVALID_RESPONSE, $result->get_error_code() );
		$this->assertStringContainsString( 'empty', $result->get_error_message() );
	}

	public function test_get_catalog_returns_error_when_entry_missing_product_slug(): void {
		$json = wp_json_encode(
			[
				[
					'tiers'    => [],
					'features' => [],
				],
			]
		);

		$this->mock->add_response( new Response( 200, [], $json ) );

		$result = $this->client->get_catalog();

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( Error_Code::INVALID_RESPONSE, $result->get_error_code() );
		$this->assertStringContainsString( 'product_slug', $result->get_error_message() );
	}

	public function test_get_catalog_parses_tiers_and_features(): void {
		$this->mock->add_response( new Response( 200, [], $this->build_catalog_json() ) );

		$result  = $this->client->get_catalog();
		$kadence = $result->get( 'kadence' );

		$this->assertNotNull( $kadence );
		$this->assertCount( 2, $kadence->get_tiers() );
		$this->assertCount( 1, $kadence->get_features() );
	}

	/**
	 * Build a minimal valid catalog JSON string.
	 *
	 * @return string
	 */
	private function build_catalog_json(): string {
		return (string) wp_json_encode(
			[
				[
					'product_slug' => 'kadence',
					'tiers'        => [
						[
							'slug' => 'kadence-basic',
							'name' => 'Basic',
							'rank' => 1,
						],
						[
							'slug' => 'kadence-pro',
							'name' => 'Pro',
							'rank' => 2,
						],
					],
					'features'     => [
						[
							'feature_slug' => 'kad-blocks-pro',
							'type'         => 'plugin',
							'minimum_tier' => 'kadence-basic',
							'plugin_file'  => 'kadence-blocks-pro/kadence-blocks-pro.php',
							'name'         => 'Blocks Pro',
							'description'  => 'Premium blocks.',
						],
					],
				],
				[
					'product_slug' => 'give',
					'tiers'        => [
						[
							'slug' => 'give-basic',
							'name' => 'Basic',
							'rank' => 1,
						],
					],
					'features'     => [],
				],
			]
		);
	}
}
