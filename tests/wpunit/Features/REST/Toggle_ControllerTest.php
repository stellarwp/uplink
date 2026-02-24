<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Features\REST;

use StellarWP\Uplink\Features\API\Client;
use StellarWP\Uplink\Features\Collection;
use StellarWP\Uplink\Features\Contracts\Feature_Strategy;
use StellarWP\Uplink\Features\Manager;
use StellarWP\Uplink\Features\REST\Toggle_Controller;
use StellarWP\Uplink\Features\Strategy\Resolver;
use StellarWP\Uplink\Features\Types\Feature;
use StellarWP\Uplink\Tests\Traits\With_Uopz;
use StellarWP\Uplink\Tests\UplinkTestCase;
use WP_REST_Request;
use WP_REST_Server;

final class Toggle_ControllerTest extends UplinkTestCase {

	use With_Uopz;

	/**
	 * The WP REST server instance.
	 *
	 * @var WP_REST_Server
	 */
	private WP_REST_Server $server;

	/**
	 * The feature manager instance.
	 *
	 * @var Manager
	 */
	private Manager $manager;

	/**
	 * Sets up the REST server and registers toggle routes before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$collection = new Collection();
		$collection->add( $this->makeEmpty( Feature::class, [ 'get_slug' => 'test-feature' ] ) );

		$mock_strategy = $this->makeEmpty( Feature_Strategy::class, [
			'enable'    => true,
			'disable'   => true,
			'is_active' => false,
		] );

		$resolver = $this->makeEmpty( Resolver::class, [
			'resolve' => $mock_strategy,
		] );

		$catalog = $this->makeEmpty( Client::class, [
			'get_features' => $collection,
		] );

		$this->manager = new Manager( $catalog, $resolver );

		/** @var WP_REST_Server $wp_rest_server */
		global $wp_rest_server;
		$this->server = $wp_rest_server = new WP_REST_Server();

		// Allow registering routes outside of the rest_api_init hook.
		$this->set_fn_return( 'did_action', function ( $hook_name ) {
			if ( $hook_name !== 'rest_api_init' ) {
				return \did_action( $hook_name );
			}

			return true;
		}, true );

		$controller = new Toggle_Controller( $this->manager );
		$controller->register_routes();
	}

	/**
	 * Resets the global REST server after each test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		global $wp_rest_server;
		$wp_rest_server = null;

		parent::tearDown();
	}

	/**
	 * Tests an administrator can enable a feature via the REST endpoint.
	 *
	 * @return void
	 */
	public function test_enable_returns_success_for_admin(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$request  = new WP_REST_Request( 'POST', '/stellarwp/uplink/v1/features/test-feature/enable' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertSame( 'test-feature', $data['slug'] );
		$this->assertTrue( $data['enabled'] );
	}

	/**
	 * Tests an administrator can disable a feature via the REST endpoint.
	 *
	 * @return void
	 */
	public function test_disable_returns_success_for_admin(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$request  = new WP_REST_Request( 'POST', '/stellarwp/uplink/v1/features/test-feature/disable' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertSame( 'test-feature', $data['slug'] );
		$this->assertFalse( $data['enabled'] );
	}

	/**
	 * Tests that enabling a feature requires the manage_options capability.
	 *
	 * @return void
	 */
	public function test_enable_requires_manage_options(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$request  = new WP_REST_Request( 'POST', '/stellarwp/uplink/v1/features/test-feature/enable' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 403, $response->get_status() );

		// Grant manage_options and verify access is allowed.
		wp_get_current_user()->add_cap( 'manage_options' );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
	}

	/**
	 * Tests that disabling a feature requires the manage_options capability.
	 *
	 * @return void
	 */
	public function test_disable_requires_manage_options(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$request  = new WP_REST_Request( 'POST', '/stellarwp/uplink/v1/features/test-feature/disable' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 403, $response->get_status() );

		// Grant manage_options and verify access is allowed.
		wp_get_current_user()->add_cap( 'manage_options' );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
	}

	/**
	 * Tests that unauthenticated requests are rejected with a 401 status.
	 *
	 * @return void
	 */
	public function test_unauthenticated_request_is_rejected(): void {
		wp_set_current_user( 0 );

		$request  = new WP_REST_Request( 'POST', '/stellarwp/uplink/v1/features/test-feature/enable' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 401, $response->get_status() );
	}

	/**
	 * Tests an invalid feature slug returns a 400 error.
	 *
	 * @return void
	 */
	public function test_invalid_slug_returns_error(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$request  = new WP_REST_Request( 'POST', '/stellarwp/uplink/v1/features/nonexistent/enable' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
	}

	/**
	 * Tests that the response schema contains the expected slug and enabled properties.
	 *
	 * @return void
	 */
	public function test_schema_has_expected_properties(): void {
		$controller = new Toggle_Controller( $this->manager );
		$schema     = $controller->get_item_schema();

		$this->assertArrayHasKey( 'properties', $schema );
		$this->assertArrayHasKey( 'slug', $schema['properties'] );
		$this->assertArrayHasKey( 'enabled', $schema['properties'] );
		$this->assertSame( 'string', $schema['properties']['slug']['type'] );
		$this->assertSame( 'boolean', $schema['properties']['enabled']['type'] );
	}
}
