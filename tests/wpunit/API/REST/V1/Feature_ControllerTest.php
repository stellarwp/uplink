<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\API\REST\V1;

use StellarWP\Uplink\Features\Error_Code;
use StellarWP\Uplink\Features\Feature_Collection;
use StellarWP\Uplink\Features\Feature_Repository;
use StellarWP\Uplink\Features\Contracts\Strategy;
use StellarWP\Uplink\Features\Manager;
use StellarWP\Uplink\API\REST\V1\Feature_Controller;
use StellarWP\Uplink\Features\Strategy\Strategy_Factory;
use StellarWP\Uplink\Features\Types\Feature;
use StellarWP\Uplink\Features\Types\Flag;
use StellarWP\Uplink\Features\Types\Plugin;
use StellarWP\Uplink\Tests\Traits\With_Uopz;
use StellarWP\Uplink\Tests\UplinkTestCase;
use WP_Error;
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
			Plugin::from_array(
				[
					'slug'              => 'feature-alpha',
					'group'             => 'GroupA',
					'tier'              => 'Tier 1',
					'name'              => 'Feature Alpha',
					'description'       => 'Alpha description',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/alpha',
				]
			)
		);
		$collection->add(
			Flag::from_array(
				[
					'slug'              => 'feature-beta',
					'group'             => 'GroupB',
					'tier'              => 'Tier 2',
					'name'              => 'Feature Beta',
					'description'       => 'Beta description',
					'is_available'      => false,
					'documentation_url' => 'https://example.com/beta',
				]
			)
		);

		$active = false;

		$mock_strategy = $this->makeEmpty(
			Strategy::class,
			[
				'enable'    => static function () use ( &$active ) {
					$active = true;

					return true;
				},
				'disable'   => static function () use ( &$active ) {
					$active = false;

					return true;
				},
				'is_active' => static function () use ( &$active ) {
					return $active;
				},
			]
		);

		$factory = $this->makeEmpty(
			Strategy_Factory::class,
			[
				'make' => $mock_strategy,
			]
		);

		$repository = $this->makeEmpty(
			Feature_Repository::class,
			[
				'get' => $collection,
			]
		);

		$this->manager = new Manager( $repository, $factory, 'test-key', 'example.com' );

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
		$request->set_param( 'type', 'flag' );
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
	 * Tests filtering by multiple params simultaneously narrows results.
	 *
	 * @return void
	 */
	public function test_list_filters_by_combined_group_and_tier(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$request = new WP_REST_Request( 'GET', '/stellarwp/uplink/v1/features' );
		$request->set_param( 'group', 'GroupA' );
		$request->set_param( 'tier', 'Tier 1' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertCount( 1, $data );
		$this->assertSame( 'feature-alpha', $data[0]['slug'] );
	}

	/**
	 * Tests that contradicting combined filters return an empty array.
	 *
	 * @return void
	 */
	public function test_list_combined_filters_no_match(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$request = new WP_REST_Request( 'GET', '/stellarwp/uplink/v1/features' );
		$request->set_param( 'group', 'GroupA' );
		$request->set_param( 'tier', 'Tier 2' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertCount( 0, $response->get_data() );
	}

	/**
	 * Tests filtering by available=false returns only unavailable features.
	 *
	 * @return void
	 */
	public function test_list_filters_available_false_returns_unavailable(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$request = new WP_REST_Request( 'GET', '/stellarwp/uplink/v1/features' );
		$request->set_param( 'available', false );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertCount( 1, $data );
		$this->assertSame( 'feature-beta', $data[0]['slug'] );
	}

	/**
	 * Tests that every feature in the list includes the is_enabled field.
	 *
	 * @return void
	 */
	public function test_list_includes_is_enabled_for_each_feature(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$request  = new WP_REST_Request( 'GET', '/stellarwp/uplink/v1/features' );
		$response = $this->server->dispatch( $request );

		foreach ( $response->get_data() as $feature ) {
			$this->assertArrayHasKey( 'is_enabled', $feature, "Feature {$feature['slug']} missing is_enabled." );
		}
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
		$this->assertSame( 'plugin', $data['type'] );
		$this->assertTrue( $data['is_available'] );
		$this->assertSame( 'https://example.com/alpha', $data['documentation_url'] );
		$this->assertArrayHasKey( 'is_enabled', $data );
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

	/**
	 * Tests that get single feature returns the correct is_enabled boolean value.
	 *
	 * @return void
	 */
	public function test_get_single_feature_returns_correct_is_enabled_value(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$request  = new WP_REST_Request( 'GET', '/stellarwp/uplink/v1/features/feature-alpha' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertFalse( $response->get_data()['is_enabled'] );
	}

	/**
	 * Tests that a catalog error on get_item returns a mapped HTTP status.
	 *
	 * @return void
	 */
	public function test_get_item_catalog_error_has_http_status(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$repository = $this->makeEmpty(
			Feature_Repository::class,
			[
				'get' => new WP_Error(
					Error_Code::FEATURE_REQUEST_FAILED,
					'Upstream API failed.'
				),
			]
		);

		$factory = $this->makeEmpty( Strategy_Factory::class );
		$manager = new Manager( $repository, $factory, 'test-key', 'example.com' );

		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;

		$controller = new Feature_Controller( $manager );
		$controller->register_routes();

		$request  = new WP_REST_Request( 'GET', '/stellarwp/uplink/v1/features/feature-alpha' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 502, $response->get_status() );
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
		$this->assertTrue( $data['is_enabled'] );
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

	/**
	 * Tests that the enable response contains all expected feature fields.
	 *
	 * @return void
	 */
	public function test_enable_returns_complete_feature_data(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$request  = new WP_REST_Request( 'POST', '/stellarwp/uplink/v1/features/feature-alpha/enable' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertSame( 'feature-alpha', $data['slug'] );
		$this->assertSame( 'Feature Alpha', $data['name'] );
		$this->assertSame( 'Alpha description', $data['description'] );
		$this->assertSame( 'GroupA', $data['group'] );
		$this->assertSame( 'Tier 1', $data['tier'] );
		$this->assertSame( 'plugin', $data['type'] );
		$this->assertTrue( $data['is_available'] );
		$this->assertSame( 'https://example.com/alpha', $data['documentation_url'] );
		$this->assertTrue( $data['is_enabled'] );
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
		$this->assertFalse( $data['is_enabled'] );
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

	/**
	 * Tests that the disable response contains all expected feature fields.
	 *
	 * @return void
	 */
	public function test_disable_returns_complete_feature_data(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$request  = new WP_REST_Request( 'POST', '/stellarwp/uplink/v1/features/feature-alpha/disable' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertSame( 'feature-alpha', $data['slug'] );
		$this->assertSame( 'Feature Alpha', $data['name'] );
		$this->assertSame( 'Alpha description', $data['description'] );
		$this->assertSame( 'GroupA', $data['group'] );
		$this->assertSame( 'Tier 1', $data['tier'] );
		$this->assertSame( 'plugin', $data['type'] );
		$this->assertTrue( $data['is_available'] );
		$this->assertSame( 'https://example.com/alpha', $data['documentation_url'] );
		$this->assertFalse( $data['is_enabled'] );
	}

	// ── Enable/Disable lifecycle ────────────────────────────────────────

	/**
	 * Tests that enable and disable round-trip correctly updates is_enabled state.
	 *
	 * @return void
	 */
	public function test_enable_disable_round_trip_updates_is_enabled(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$get = new WP_REST_Request( 'GET', '/stellarwp/uplink/v1/features/feature-alpha' );

		// Initially disabled.
		$response = $this->server->dispatch( $get );
		$this->assertFalse( $response->get_data()['is_enabled'] );

		// Enable.
		$enable   = new WP_REST_Request( 'POST', '/stellarwp/uplink/v1/features/feature-alpha/enable' );
		$response = $this->server->dispatch( $enable );
		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['is_enabled'] );

		// GET reflects enabled state.
		$response = $this->server->dispatch( $get );
		$this->assertTrue( $response->get_data()['is_enabled'] );

		// Disable.
		$disable  = new WP_REST_Request( 'POST', '/stellarwp/uplink/v1/features/feature-alpha/disable' );
		$response = $this->server->dispatch( $disable );
		$this->assertSame( 200, $response->get_status() );
		$this->assertFalse( $response->get_data()['is_enabled'] );

		// GET reflects disabled state.
		$response = $this->server->dispatch( $get );
		$this->assertFalse( $response->get_data()['is_enabled'] );
	}

	// ── Error status mapping ────────────────────────────────────────────

	/**
	 * Tests that a WP_Error from Manager::enable() gets an HTTP status code.
	 *
	 * @return void
	 */
	public function test_enable_strategy_error_has_http_status(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$error_strategy = $this->makeEmpty(
			Strategy::class,
			[
				'enable'    => new WP_Error(
					Error_Code::INSTALL_LOCKED,
					'Concurrent install in progress.'
				),
				'disable'   => true,
				'is_active' => false,
			]
		);

		$factory    = $this->makeEmpty( Strategy_Factory::class, [ 'make' => $error_strategy ] );
		$repository = $this->makeEmpty( Feature_Repository::class, [ 'get' => $this->manager->get_all() ] );
		$manager    = new Manager( $repository, $factory, 'test-key', 'example.com' );

		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;

		$controller = new Feature_Controller( $manager );
		$controller->register_routes();

		$request  = new WP_REST_Request( 'POST', '/stellarwp/uplink/v1/features/feature-alpha/enable' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 409, $response->get_status() );
	}

	/**
	 * Tests that a WP_Error from Manager::get_features() gets an HTTP status code on list.
	 *
	 * @return void
	 */
	public function test_list_catalog_error_has_http_status(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$repository = $this->makeEmpty(
			Feature_Repository::class,
			[
				'get' => new WP_Error(
					Error_Code::FEATURE_REQUEST_FAILED,
					'Upstream API failed.'
				),
			]
		);

		$factory = $this->makeEmpty( Strategy_Factory::class );
		$manager = new Manager( $repository, $factory, 'test-key', 'example.com' );

		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;

		$controller = new Feature_Controller( $manager );
		$controller->register_routes();

		$request  = new WP_REST_Request( 'GET', '/stellarwp/uplink/v1/features' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 502, $response->get_status() );
	}

	/**
	 * Tests that a WP_Error from Manager::disable() gets an HTTP status code.
	 *
	 * @return void
	 */
	public function test_disable_strategy_error_has_http_status(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$error_strategy = $this->makeEmpty(
			Strategy::class,
			[
				'enable'    => true,
				'disable'   => new WP_Error(
					Error_Code::DEACTIVATION_FAILED,
					'Plugin deactivation did not take effect.'
				),
				'is_active' => true,
			]
		);

		$factory    = $this->makeEmpty( Strategy_Factory::class, [ 'make' => $error_strategy ] );
		$repository = $this->makeEmpty( Feature_Repository::class, [ 'get' => $this->manager->get_all() ] );
		$manager    = new Manager( $repository, $factory, 'test-key', 'example.com' );

		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;

		$controller = new Feature_Controller( $manager );
		$controller->register_routes();

		$request  = new WP_REST_Request( 'POST', '/stellarwp/uplink/v1/features/feature-alpha/disable' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 409, $response->get_status() );
	}

	/**
	 * Tests that an error with a pre-existing HTTP status is not overridden.
	 *
	 * @return void
	 */
	public function test_error_with_existing_status_is_preserved(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$error_strategy = $this->makeEmpty(
			Strategy::class,
			[
				'enable'    => new WP_Error(
					Error_Code::INSTALL_FAILED,
					'Install failed.',
					[ 'status' => 503 ]
				),
				'disable'   => true,
				'is_active' => false,
			]
		);

		$factory    = $this->makeEmpty( Strategy_Factory::class, [ 'make' => $error_strategy ] );
		$repository = $this->makeEmpty( Feature_Repository::class, [ 'get' => $this->manager->get_all() ] );
		$manager    = new Manager( $repository, $factory, 'test-key', 'example.com' );

		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;

		$controller = new Feature_Controller( $manager );
		$controller->register_routes();

		$request  = new WP_REST_Request( 'POST', '/stellarwp/uplink/v1/features/feature-alpha/enable' );
		$response = $this->server->dispatch( $request );

		// The original 503 status should be preserved, not overridden by the error code mapping (422).
		$this->assertSame( 503, $response->get_status() );
	}

	// ── Schema ──────────────────────────────────────────────────────────

	/**
	 * Tests that the feature schema uses oneOf with three type variants.
	 *
	 * @return void
	 */
	public function test_schema_uses_one_of_with_three_variants(): void {
		$controller = new Feature_Controller( $this->manager );
		$schema     = $controller->get_item_schema();

		$this->assertArrayHasKey( 'oneOf', $schema );
		$this->assertCount( 3, $schema['oneOf'] );
	}

	/**
	 * Tests that the plugin variant includes plugin-specific and installable properties.
	 *
	 * @return void
	 */
	public function test_schema_plugin_variant_has_all_properties(): void {
		$controller = new Feature_Controller( $this->manager );
		$schema     = $controller->get_item_schema();
		$plugin     = $schema['oneOf'][0];

		$this->assertSame( 'plugin', $plugin['title'] );
		$this->assertTrue( $plugin['additionalProperties'] );
		$this->assertSame( [ Feature::TYPE_PLUGIN ], $plugin['properties']['type']['enum'] );

		$expected = [ 'slug', 'name', 'description', 'group', 'tier', 'type', 'is_available', 'documentation_url', 'is_enabled', 'plugin_file', 'plugin_slug', 'authors', 'is_dot_org' ];

		foreach ( $expected as $property ) {
			$this->assertArrayHasKey( $property, $plugin['properties'], "Missing plugin schema property: {$property}" );
		}

		$this->assertCount( count( $expected ), $plugin['properties'] );
	}

	/**
	 * Tests that the theme variant includes installable properties but not plugin-specific ones.
	 *
	 * @return void
	 */
	public function test_schema_theme_variant_has_installable_properties(): void {
		$controller = new Feature_Controller( $this->manager );
		$schema     = $controller->get_item_schema();
		$theme      = $schema['oneOf'][1];

		$this->assertSame( 'theme', $theme['title'] );
		$this->assertSame( [ Feature::TYPE_THEME ], $theme['properties']['type']['enum'] );

		$expected = [ 'slug', 'name', 'description', 'group', 'tier', 'type', 'is_available', 'documentation_url', 'is_enabled', 'authors', 'is_dot_org' ];

		foreach ( $expected as $property ) {
			$this->assertArrayHasKey( $property, $theme['properties'], "Missing theme schema property: {$property}" );
		}

		$this->assertArrayNotHasKey( 'plugin_file', $theme['properties'] );
		$this->assertArrayNotHasKey( 'plugin_slug', $theme['properties'] );
		$this->assertCount( count( $expected ), $theme['properties'] );
	}

	/**
	 * Tests that the flag variant has only base properties.
	 *
	 * @return void
	 */
	public function test_schema_flag_variant_has_only_base_properties(): void {
		$controller = new Feature_Controller( $this->manager );
		$schema     = $controller->get_item_schema();
		$flag       = $schema['oneOf'][2];

		$this->assertSame( 'flag', $flag['title'] );
		$this->assertSame( [ Feature::TYPE_FLAG ], $flag['properties']['type']['enum'] );

		$expected = [ 'slug', 'name', 'description', 'group', 'tier', 'type', 'is_available', 'documentation_url', 'is_enabled' ];

		foreach ( $expected as $property ) {
			$this->assertArrayHasKey( $property, $flag['properties'], "Missing flag schema property: {$property}" );
		}

		$this->assertArrayNotHasKey( 'plugin_file', $flag['properties'] );
		$this->assertArrayNotHasKey( 'plugin_slug', $flag['properties'] );
		$this->assertArrayNotHasKey( 'authors', $flag['properties'] );
		$this->assertArrayNotHasKey( 'is_dot_org', $flag['properties'] );
		$this->assertCount( count( $expected ), $flag['properties'] );
	}
}
