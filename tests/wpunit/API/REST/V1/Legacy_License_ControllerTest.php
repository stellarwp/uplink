<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\API\REST\V1;

use StellarWP\Uplink\API\REST\V1\Legacy_License_Controller;
use StellarWP\Uplink\Legacy\License_Repository;
use StellarWP\Uplink\Tests\Traits\With_Uopz;
use StellarWP\Uplink\Tests\UplinkTestCase;
use WP_REST_Request;
use WP_REST_Server;

final class Legacy_License_ControllerTest extends UplinkTestCase {

	use With_Uopz;

	private WP_REST_Server $server;
	private License_Repository $repository;

	/**
	 * Sample legacy license data used across tests.
	 *
	 * @var array<string, mixed>
	 */
	private array $license_data = [
		'key'        => 'ABC123',
		'slug'       => 'my-plugin',
		'name'       => 'My Plugin',
		'product'    => 'My Product',
		'is_active'  => true,
		'page_url'   => 'https://example.com/account',
		'expires_at' => '2027-01-01',
	];

	protected function setUp(): void {
		parent::setUp();

		$this->repository = new License_Repository();

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

		$controller = new Legacy_License_Controller( $this->repository );
		$controller->register_routes();
	}

	protected function tearDown(): void {
		global $wp_rest_server;
		$wp_rest_server = null;

		parent::tearDown();
	}

	public function test_returns_empty_array_when_no_legacy_licenses(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$request  = new WP_REST_Request( 'GET', '/stellarwp/uplink/v1/legacy-licenses' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( [], $response->get_data() );
	}

	public function test_returns_legacy_licenses(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		add_filter(
			'stellarwp/uplink/legacy_licenses',
			function () {
				return [ $this->license_data ];
			}
		);

		$request  = new WP_REST_Request( 'GET', '/stellarwp/uplink/v1/legacy-licenses' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertCount( 1, $response->get_data() );
	}

	public function test_returns_expected_shape(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		add_filter(
			'stellarwp/uplink/legacy_licenses',
			function () {
				return [ $this->license_data ];
			}
		);

		$request  = new WP_REST_Request( 'GET', '/stellarwp/uplink/v1/legacy-licenses' );
		$response = $this->server->dispatch( $request );
		$item     = $response->get_data()[0];

		$this->assertArrayHasKey( 'key', $item );
		$this->assertArrayHasKey( 'slug', $item );
		$this->assertArrayHasKey( 'name', $item );
		$this->assertArrayHasKey( 'product', $item );
		$this->assertArrayHasKey( 'is_active', $item );
		$this->assertArrayHasKey( 'page_url', $item );
		$this->assertArrayHasKey( 'expires_at', $item );

		$this->assertSame( 'ABC123', $item['key'] );
		$this->assertSame( 'my-plugin', $item['slug'] );
		$this->assertSame( 'My Plugin', $item['name'] );
		$this->assertSame( 'My Product', $item['product'] );
		$this->assertTrue( $item['is_active'] );
		$this->assertSame( 'https://example.com/account', $item['page_url'] );
		$this->assertSame( '2027-01-01', $item['expires_at'] );
	}

	public function test_returns_multiple_licenses(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		add_filter(
			'stellarwp/uplink/legacy_licenses',
			function () {
				return [
					$this->license_data,
					array_merge(
						$this->license_data,
						[
							'slug' => 'another-plugin',
							'name' => 'Another Plugin',
						]
					),
				];
			}
		);

		$request  = new WP_REST_Request( 'GET', '/stellarwp/uplink/v1/legacy-licenses' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertCount( 2, $response->get_data() );
	}

	public function test_requires_manage_options(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$request  = new WP_REST_Request( 'GET', '/stellarwp/uplink/v1/legacy-licenses' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 403, $response->get_status() );
	}

	public function test_rejects_unauthenticated(): void {
		wp_set_current_user( 0 );

		$request  = new WP_REST_Request( 'GET', '/stellarwp/uplink/v1/legacy-licenses' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 401, $response->get_status() );
	}

	public function test_schema_has_expected_properties(): void {
		$controller = new Legacy_License_Controller( $this->repository );
		$schema     = $controller->get_item_schema();

		$this->assertArrayHasKey( 'properties', $schema );

		$expected = [ 'key', 'slug', 'name', 'product', 'is_active', 'page_url', 'expires_at' ];

		foreach ( $expected as $property ) {
			$this->assertArrayHasKey( $property, $schema['properties'], "Missing schema property: {$property}" );
		}
	}
}
