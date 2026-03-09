<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\API\REST\V1;

use StellarWP\Uplink\Licensing\Error_Code;
use StellarWP\Uplink\Licensing\Fixture_Client;
use StellarWP\Uplink\Licensing\License_Manager;
use StellarWP\Uplink\Licensing\Registry\Product_Registry;
use StellarWP\Uplink\Licensing\Repositories\License_Repository;
use StellarWP\Uplink\API\REST\V1\License_Controller;
use StellarWP\Uplink\Site\Data;
use StellarWP\Uplink\Tests\Traits\With_Uopz;
use StellarWP\Uplink\Tests\UplinkTestCase;
use WP_REST_Request;
use WP_REST_Server;

final class License_ControllerTest extends UplinkTestCase {

	use With_Uopz;

	private WP_REST_Server $server;
	private License_Manager $manager;

	protected function setUp(): void {
		parent::setUp();

		delete_option( License_Repository::KEY_OPTION_NAME );
		delete_option( License_Repository::PRODUCTS_STATE_OPTION_NAME );

		$repository    = new License_Repository();
		$registry      = new Product_Registry();
		$this->manager = new License_Manager( $repository, $registry, new Fixture_Client( codecept_data_dir( 'licensing' ) ) );

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

		$controller = new License_Controller( $this->manager, new Data() );
		$controller->register_routes();
	}

	protected function tearDown(): void {
		global $wp_rest_server;
		$wp_rest_server = null;

		delete_option( License_Repository::KEY_OPTION_NAME );
		delete_option( License_Repository::PRODUCTS_STATE_OPTION_NAME );

		parent::tearDown();
	}

	// -------------------------------------------------------------------------
	// GET
	// -------------------------------------------------------------------------

	public function test_get_returns_null_key_when_none_stored(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$request  = new WP_REST_Request( 'GET', '/stellarwp/uplink/v1/license' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertNull( $response->get_data()['key'] );
		$this->assertSame( [], $response->get_data()['products'] );
	}

	public function test_get_returns_stored_key(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$this->manager->store_key( 'LWSW-UNIFIED-PRO-2026' );

		$request  = new WP_REST_Request( 'GET', '/stellarwp/uplink/v1/license' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'LWSW-UNIFIED-PRO-2026', $response->get_data()['key'] );
	}

	public function test_get_returns_products_array(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$this->manager->store_key( 'LWSW-UNIFIED-PRO-2026' );

		$request  = new WP_REST_Request( 'GET', '/stellarwp/uplink/v1/license' );
		$response = $this->server->dispatch( $request );

		$products = $response->get_data()['products'];

		$this->assertIsArray( $products );
		$this->assertNotEmpty( $products );
		$this->assertArrayHasKey( 'product_slug', $products[0] );
	}

	public function test_get_requires_manage_options(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$request  = new WP_REST_Request( 'GET', '/stellarwp/uplink/v1/license' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 403, $response->get_status() );
	}

	public function test_get_rejects_unauthenticated(): void {
		wp_set_current_user( 0 );

		$request  = new WP_REST_Request( 'GET', '/stellarwp/uplink/v1/license' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 401, $response->get_status() );
	}

	// -------------------------------------------------------------------------
	// POST /license
	// -------------------------------------------------------------------------

	public function test_store_saves_key_and_returns_it(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$request = new WP_REST_Request( 'POST', '/stellarwp/uplink/v1/license' );
		$request->set_param( 'key', 'LWSW-UNIFIED-PRO-2026' );

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'LWSW-UNIFIED-PRO-2026', $data['key'] );
	}

	public function test_store_persists_to_options(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$request = new WP_REST_Request( 'POST', '/stellarwp/uplink/v1/license' );
		$request->set_param( 'key', 'LWSW-UNIFIED-PRO-2026' );

		$this->server->dispatch( $request );

		$this->assertSame( 'LWSW-UNIFIED-PRO-2026', get_option( License_Repository::KEY_OPTION_NAME ) );
	}

	public function test_store_requires_key_param(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$request  = new WP_REST_Request( 'POST', '/stellarwp/uplink/v1/license' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
	}

	public function test_store_rejects_key_without_lwsw_prefix(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$request = new WP_REST_Request( 'POST', '/stellarwp/uplink/v1/license' );
		$request->set_param( 'key', 'INVALID-KEY-NO-PREFIX' );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
	}

	public function test_store_requires_manage_options(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$request = new WP_REST_Request( 'POST', '/stellarwp/uplink/v1/license' );
		$request->set_param( 'key', 'LWSW-UNIFIED-PRO-2026' );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 403, $response->get_status() );
	}

	// -------------------------------------------------------------------------
	// POST /license/validate
	// -------------------------------------------------------------------------

	public function test_validate_returns_201_on_success(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$this->manager->store_key( 'LWSW-UNIFIED-PRO-2026' );

		$request = new WP_REST_Request( 'POST', '/stellarwp/uplink/v1/license/validate' );
		$request->set_param( 'product_slug', 'give' );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 201, $response->get_status() );
		$this->assertNull( $response->get_data() );
	}

	public function test_validate_requires_product_slug(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$this->manager->store_key( 'LWSW-UNIFIED-PRO-2026' );

		$request  = new WP_REST_Request( 'POST', '/stellarwp/uplink/v1/license/validate' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
	}

	public function test_validate_returns_error_when_no_key_stored(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$request = new WP_REST_Request( 'POST', '/stellarwp/uplink/v1/license/validate' );
		$request->set_param( 'product_slug', 'give' );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 422, $response->get_status() );
	}

	public function test_validate_returns_error_for_unknown_product(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$this->manager->store_key( 'LWSW-UNIFIED-PRO-2026' );

		$request = new WP_REST_Request( 'POST', '/stellarwp/uplink/v1/license/validate' );
		$request->set_param( 'product_slug', 'unknown-product' );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 422, $response->get_status() );
	}

	public function test_validate_requires_manage_options(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$request = new WP_REST_Request( 'POST', '/stellarwp/uplink/v1/license/validate' );
		$request->set_param( 'product_slug', 'give' );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 403, $response->get_status() );
	}

	// -------------------------------------------------------------------------
	// DELETE
	// -------------------------------------------------------------------------

	public function test_delete_removes_stored_key(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$this->manager->store_key( 'LWSW-UNIFIED-PRO-2026' );

		$request  = new WP_REST_Request( 'DELETE', '/stellarwp/uplink/v1/license' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 204, $response->get_status() );
		$this->assertNull( $response->get_data() );
		$this->assertNull( $this->manager->get_key() );
	}

	public function test_delete_requires_manage_options(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$request  = new WP_REST_Request( 'DELETE', '/stellarwp/uplink/v1/license' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 403, $response->get_status() );
	}

	// -------------------------------------------------------------------------
	// Schema
	// -------------------------------------------------------------------------

	public function test_schema_has_key_property(): void {
		$controller = new License_Controller( $this->manager, new Data() );
		$schema     = $controller->get_item_schema();

		$this->assertArrayHasKey( 'properties', $schema );
		$this->assertArrayHasKey( 'key', $schema['properties'] );
	}

	public function test_store_rejects_key_not_recognized_by_api(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$request = new WP_REST_Request( 'POST', '/stellarwp/uplink/v1/license' );
		$request->set_param( 'key', 'LWSW-NOT-A-REAL-KEY' );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
	}

	public function test_store_does_not_persist_key_not_recognized_by_api(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$request = new WP_REST_Request( 'POST', '/stellarwp/uplink/v1/license' );
		$request->set_param( 'key', 'LWSW-NOT-A-REAL-KEY' );

		$this->server->dispatch( $request );

		$this->assertEmpty( get_option( License_Repository::KEY_OPTION_NAME ) );
	}

	// -------------------------------------------------------------------------
	// Error codes
	// -------------------------------------------------------------------------

	public function test_store_error_code_constant(): void {
		$this->assertSame( 'stellarwp-uplink-store-failed', Error_Code::STORE_FAILED );
	}
}
