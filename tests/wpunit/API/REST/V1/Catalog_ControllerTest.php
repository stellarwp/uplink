<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\API\REST\V1;

use StellarWP\Uplink\Catalog\Catalog_Collection;
use StellarWP\Uplink\Catalog\Catalog_Repository;
use StellarWP\Uplink\Catalog\Clients\Catalog_Client;
use StellarWP\Uplink\Catalog\Error_Code;
use StellarWP\Uplink\API\REST\V1\Catalog_Controller;
use StellarWP\Uplink\Catalog\Results\Product_Catalog;
use StellarWP\Uplink\Tests\Traits\With_Uopz;
use StellarWP\Uplink\Tests\UplinkTestCase;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

final class Catalog_ControllerTest extends UplinkTestCase {

	use With_Uopz;

	private WP_REST_Server $server;

	protected function setUp(): void {
		parent::setUp();

		delete_option( Catalog_Repository::CATALOG_STATE_OPTION_NAME );

		$client     = $this->make_client( $this->build_catalog_from_fixture() );
		$repository = new Catalog_Repository( $client );

		/** @var WP_REST_Server $wp_rest_server */
		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;

		// Allow registering routes outside of the rest_api_init hook.
		$this->set_fn_return(
			'did_action',
			function ( $hook_name ) {
				if ( $hook_name !== 'rest_api_init' ) {
					return \did_action( $hook_name );
				}

				return true;
			},
			true
		);

		$controller = new Catalog_Controller( $repository );
		$controller->register_routes();
	}

	protected function tearDown(): void {
		global $wp_rest_server;
		$wp_rest_server = null;

		delete_option( Catalog_Repository::CATALOG_STATE_OPTION_NAME );

		parent::tearDown();
	}

	public function test_list_returns_all_catalogs(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$request  = new WP_REST_Request( 'GET', '/stellarwp/uplink/v1/catalog' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertCount( 4, $response->get_data() );
	}

	public function test_list_returns_expected_shape(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$request  = new WP_REST_Request( 'GET', '/stellarwp/uplink/v1/catalog' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$first = $data[0];

		$this->assertArrayHasKey( 'product_slug', $first );
		$this->assertArrayHasKey( 'tiers', $first );
		$this->assertArrayHasKey( 'features', $first );
		$this->assertIsArray( $first['tiers'] );
		$this->assertIsArray( $first['features'] );
	}

	public function test_list_requires_manage_options(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$request  = new WP_REST_Request( 'GET', '/stellarwp/uplink/v1/catalog' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 403, $response->get_status() );
	}

	public function test_list_rejects_unauthenticated(): void {
		wp_set_current_user( 0 );

		$request  = new WP_REST_Request( 'GET', '/stellarwp/uplink/v1/catalog' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 401, $response->get_status() );
	}

	public function test_client_error_is_forwarded(): void {
		$error      = new WP_Error( Error_Code::INVALID_RESPONSE, 'API unavailable.' );
		$client     = $this->make_client( $error );
		$repository = new Catalog_Repository( $client );
		$controller = new Catalog_Controller( $repository );

		$response = $controller->get_items( new WP_REST_Request( 'GET', '/stellarwp/uplink/v1/catalog' ) );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertSame( Error_Code::INVALID_RESPONSE, $response->get_error_code() );
	}

	public function test_schema_has_expected_properties(): void {
		$client     = $this->make_client( new Catalog_Collection() );
		$repository = new Catalog_Repository( $client );
		$controller = new Catalog_Controller( $repository );
		$schema     = $controller->get_item_schema();

		$this->assertArrayHasKey( 'properties', $schema );

		$expected = [ 'product_slug', 'tiers', 'features' ];

		foreach ( $expected as $property ) {
			$this->assertArrayHasKey( $property, $schema['properties'], "Missing schema property: {$property}" );
		}
	}

	private function build_catalog_from_fixture(): Catalog_Collection {
		$json = file_get_contents( codecept_data_dir( 'catalog/default.json' ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$data = json_decode( $json, true );

		$collection = new Catalog_Collection();

		foreach ( $data as $item ) {
			/** @var array<string, mixed> $item */
			$collection->add( Product_Catalog::from_array( $item ) );
		}

		return $collection;
	}

	private function make_client( $result ): Catalog_Client {
		return $this->makeEmpty(
			Catalog_Client::class,
			[
				'get_catalog' => $result,
			]
		);
	}
}
