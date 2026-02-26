<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Features\REST;

use StellarWP\Uplink\Features\API\Client;
use StellarWP\Uplink\Features\Feature_Collection;
use StellarWP\Uplink\Features\Contracts\Strategy;
use StellarWP\Uplink\Features\Manager;
use StellarWP\Uplink\Features\REST\Feature_Controller;
use StellarWP\Uplink\Features\Strategy\Resolver;
use StellarWP\Uplink\Features\Types\Feature;
use StellarWP\Uplink\Tests\Traits\With_Uopz;
use StellarWP\Uplink\Tests\UplinkTestCase;
use WP_REST_Request;
use WP_REST_Server;

final class Feature_ControllerTest extends UplinkTestCase {

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
	 * Sets up the REST server and registers routes before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$collection = new Feature_Collection();
		$collection->add(
			$this->makeEmpty(
				Feature::class,
				[
					'get_slug'              => 'feature-alpha',
					'get_name'              => 'Feature Alpha',
					'get_description'       => 'Alpha description',
					'get_group'             => 'GroupA',
					'get_tier'              => 'Tier 1',
					'get_type'              => 'zip',
					'is_available'          => true,
					'get_documentation_url' => 'https://example.com/alpha',
					'to_array'              => [
						'slug'              => 'feature-alpha',
						'group'             => 'GroupA',
						'tier'              => 'Tier 1',
						'name'              => 'Feature Alpha',
						'description'       => 'Alpha description',
						'type'              => 'zip',
						'is_available'      => true,
						'documentation_url' => 'https://example.com/alpha',
					],
				] 
			) 
		);
		$collection->add(
			$this->makeEmpty(
				Feature::class,
				[
					'get_slug'              => 'feature-beta',
					'get_name'              => 'Feature Beta',
					'get_description'       => 'Beta description',
					'get_group'             => 'GroupB',
					'get_tier'              => 'Tier 2',
					'get_type'              => 'built_in',
					'is_available'          => false,
					'get_documentation_url' => 'https://example.com/beta',
					'to_array'              => [
						'slug'              => 'feature-beta',
						'group'             => 'GroupB',
						'tier'              => 'Tier 2',
						'name'              => 'Feature Beta',
						'description'       => 'Beta description',
						'type'              => 'built_in',
						'is_available'      => false,
						'documentation_url' => 'https://example.com/beta',
					],
				] 
			) 
		);

		$mock_strategy = $this->makeEmpty(
			Strategy::class,
			[
				'enable'    => true,
				'disable'   => true,
				'is_active' => false,
			] 
		);

		$resolver = $this->makeEmpty(
			Resolver::class,
			[
				'resolve' => $mock_strategy,
			] 
		);

		$catalog = $this->makeEmpty(
			Client::class,
			[
				'get_features' => $collection,
			] 
		);

		$this->manager = new Manager( $catalog, $resolver );

		/** @var WP_REST_Server $wp_rest_server */
		global $wp_rest_server;
		$this->server = $wp_rest_server = new WP_REST_Server();

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

		$controller = new Feature_Controller( $this->manager );
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

	// ── List (GET /features) ────────────────────────────────────────────

	/**
	 * Tests listing all features returns both features with full data.
	 *
	 * @return void
	 */
	public function test_list_returns_all_features(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$request  = new WP_REST_Request( 'GET', '/stellarwp/uplink/v1/features' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertCount( 2, $data );
		$this->assertSame( 'feature-alpha', $data[0]['slug'] );
		$this->assertSame( 'feature-beta', $data[1]['slug'] );
	}

	/**
	 * Tests filtering features by group.
	 *
	 * @return void
	 */
	public function test_list_filters_by_group(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$request = new WP_REST_Request( 'GET', '/stellarwp/uplink/v1/features' );
		$request->set_param( 'group', 'GroupA' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertCount( 1, $data );
		$this->assertSame( 'feature-alpha', $data[0]['slug'] );
	}

	/**
	 * Tests filtering features by tier.
	 *
	 * @return void
	 */
	public function test_list_filters_by_tier(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$request = new WP_REST_Request( 'GET', '/stellarwp/uplink/v1/features' );
		$request->set_param( 'tier', 'Tier 2' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertCount( 1, $data );
		$this->assertSame( 'feature-beta', $data[0]['slug'] );
	}

	/**
	 * Tests filtering features by availability.
	 *
	 * @return void
	 */
	public function test_list_filters_by_available(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$request = new WP_REST_Request( 'GET', '/stellarwp/uplink/v1/features' );
		$request->set_param( 'available', true );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertCount( 1, $data );
		$this->assertSame( 'feature-alpha', $data[0]['slug'] );
	}

	/**
	 * Tests filtering features by type.
	 *
	 * @return void
	 */
	public function test_list_filters_by_type(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$request = new WP_REST_Request( 'GET', '/stellarwp/uplink/v1/features' );
		$request->set_param( 'type', 'built_in' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertCount( 1, $data );
		$this->assertSame( 'feature-beta', $data[0]['slug'] );
	}

	/**
	 * Tests that filtering with no matches returns an empty array.
	 *
	 * @return void
	 */
	public function test_list_returns_empty_for_no_matches(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$request = new WP_REST_Request( 'GET', '/stellarwp/uplink/v1/features' );
		$request->set_param( 'group', 'NonexistentGroup' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertCount( 0, $response->get_data() );
	}

	/**
	 * Tests that listing features requires the manage_options capability.
	 *
	 * @return void
	 */
	public function test_list_requires_manage_options(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$request  = new WP_REST_Request( 'GET', '/stellarwp/uplink/v1/features' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 403, $response->get_status() );
	}

	/**
	 * Tests that unauthenticated requests to list are rejected.
	 *
	 * @return void
	 */
	public function test_list_rejects_unauthenticated(): void {
		wp_set_current_user( 0 );

		$request  = new WP_REST_Request( 'GET', '/stellarwp/uplink/v1/features' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 401, $response->get_status() );
	}

	// ── Get single (GET /features/{slug}) ───────────────────────────────

	/**
	 * Tests retrieving a single feature returns all fields.
	 *
	 * @return void
	 */
	public function test_get_single_feature_returns_all_fields(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$request  = new WP_REST_Request( 'GET', '/stellarwp/uplink/v1/features/feature-alpha' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertSame( 'feature-alpha', $data['slug'] );
		$this->assertSame( 'Feature Alpha', $data['name'] );
		$this->assertSame( 'Alpha description', $data['description'] );
		$this->assertSame( 'GroupA', $data['group'] );
		$this->assertSame( 'Tier 1', $data['tier'] );
		$this->assertSame( 'zip', $data['type'] );
		$this->assertTrue( $data['is_available'] );
		$this->assertSame( 'https://example.com/alpha', $data['documentation_url'] );
		$this->assertArrayHasKey( 'enabled', $data );
	}

	/**
	 * Tests that requesting an unknown slug returns 404.
	 *
	 * @return void
	 */
	public function test_get_unknown_slug_returns_404(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$request  = new WP_REST_Request( 'GET', '/stellarwp/uplink/v1/features/nonexistent' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 404, $response->get_status() );
	}

	/**
	 * Tests that getting a single feature requires the manage_options capability.
	 *
	 * @return void
	 */
	public function test_get_single_requires_manage_options(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$request  = new WP_REST_Request( 'GET', '/stellarwp/uplink/v1/features/feature-alpha' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 403, $response->get_status() );
	}

	/**
	 * Tests that unauthenticated requests to get a single feature are rejected.
	 *
	 * @return void
	 */
	public function test_get_single_rejects_unauthenticated(): void {
		wp_set_current_user( 0 );

		$request  = new WP_REST_Request( 'GET', '/stellarwp/uplink/v1/features/feature-alpha' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 401, $response->get_status() );
	}

	// ── Enable (POST /features/{slug}/enable) ───────────────────────────

	/**
	 * Tests an administrator can enable a feature via the REST endpoint.
	 *
	 * @return void
	 */
	public function test_enable_returns_success_for_admin(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$request  = new WP_REST_Request( 'POST', '/stellarwp/uplink/v1/features/feature-alpha/enable' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertSame( 'feature-alpha', $data['slug'] );
		$this->assertTrue( $data['enabled'] );
	}

	/**
	 * Tests that enabling a feature requires the manage_options capability.
	 *
	 * @return void
	 */
	public function test_enable_requires_manage_options(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$request  = new WP_REST_Request( 'POST', '/stellarwp/uplink/v1/features/feature-alpha/enable' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 403, $response->get_status() );

		// Grant manage_options and verify access is allowed.
		wp_get_current_user()->add_cap( 'manage_options' );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
	}

	/**
	 * Tests that unauthenticated enable requests are rejected with a 401 status.
	 *
	 * @return void
	 */
	public function test_enable_unauthenticated_request_is_rejected(): void {
		wp_set_current_user( 0 );

		$request  = new WP_REST_Request( 'POST', '/stellarwp/uplink/v1/features/feature-alpha/enable' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 401, $response->get_status() );
	}

	/**
	 * Tests an invalid feature slug returns a 400 error on enable.
	 *
	 * @return void
	 */
	public function test_enable_invalid_slug_returns_error(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$request  = new WP_REST_Request( 'POST', '/stellarwp/uplink/v1/features/nonexistent/enable' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
	}

	// ── Disable (POST /features/{slug}/disable) ─────────────────────────

	/**
	 * Tests an administrator can disable a feature via the REST endpoint.
	 *
	 * @return void
	 */
	public function test_disable_returns_success_for_admin(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$request  = new WP_REST_Request( 'POST', '/stellarwp/uplink/v1/features/feature-alpha/disable' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertSame( 'feature-alpha', $data['slug'] );
		$this->assertFalse( $data['enabled'] );
	}

	/**
	 * Tests that disabling a feature requires the manage_options capability.
	 *
	 * @return void
	 */
	public function test_disable_requires_manage_options(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$request  = new WP_REST_Request( 'POST', '/stellarwp/uplink/v1/features/feature-alpha/disable' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 403, $response->get_status() );

		// Grant manage_options and verify access is allowed.
		wp_get_current_user()->add_cap( 'manage_options' );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
	}

	/**
	 * Tests that unauthenticated disable requests are rejected with a 401 status.
	 *
	 * @return void
	 */
	public function test_disable_unauthenticated_request_is_rejected(): void {
		wp_set_current_user( 0 );

		$request  = new WP_REST_Request( 'POST', '/stellarwp/uplink/v1/features/feature-alpha/disable' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 401, $response->get_status() );
	}

	/**
	 * Tests an invalid feature slug returns a 400 error on disable.
	 *
	 * @return void
	 */
	public function test_disable_invalid_slug_returns_error(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$request  = new WP_REST_Request( 'POST', '/stellarwp/uplink/v1/features/nonexistent/disable' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
	}

	// ── Schema ──────────────────────────────────────────────────────────

	/**
	 * Tests that the feature schema contains all 9 expected properties.
	 *
	 * @return void
	 */
	public function test_schema_has_expected_properties(): void {
		$controller = new Feature_Controller( $this->manager );
		$schema     = $controller->get_item_schema();

		$this->assertArrayHasKey( 'properties', $schema );
		$this->assertTrue( $schema['additionalProperties'], 'Schema should allow additional properties for type-specific fields.' );

		$expected = [ 'slug', 'name', 'description', 'group', 'tier', 'type', 'is_available', 'documentation_url', 'enabled' ];

		foreach ( $expected as $property ) {
			$this->assertArrayHasKey( $property, $schema['properties'], "Missing schema property: {$property}" );
		}

		$this->assertCount( 9, $schema['properties'] );
	}

	/**
	 * Tests that the toggle routes use the same item schema.
	 *
	 * @return void
	 */
	public function test_toggle_schema_matches_item_schema(): void {
		$controller = new Feature_Controller( $this->manager );
		$schema     = $controller->get_public_item_schema();

		$this->assertArrayHasKey( 'properties', $schema );

		$expected = [ 'slug', 'name', 'description', 'group', 'tier', 'type', 'is_available', 'documentation_url', 'enabled' ];

		foreach ( $expected as $property ) {
			$this->assertArrayHasKey( $property, $schema['properties'], "Missing schema property: {$property}" );
		}

		$this->assertCount( count( $expected ), $schema['properties'] );
	}
}
