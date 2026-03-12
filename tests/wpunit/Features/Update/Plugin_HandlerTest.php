<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Features\Update;

use StellarWP\Uplink\Features\Update\Resolve_Update_Data;
use StellarWP\Uplink\Features\Feature_Repository;
use StellarWP\Uplink\Features\Feature_Collection;
use StellarWP\Uplink\Features\Types\Plugin;
use StellarWP\Uplink\Features\Update\Plugin_Handler;
use StellarWP\Uplink\Licensing\License_Manager;
use StellarWP\Uplink\Tests\UplinkTestCase;
use stdClass;
use WP_Error;

final class Plugin_HandlerTest extends UplinkTestCase {

	/**
	 * The handler under test.
	 *
	 * @var Plugin_Handler
	 */
	private Plugin_Handler $handler;

	/**
	 * Sets up the handler with mocked dependencies before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$resolver           = $this->makeEmpty( Resolve_Update_Data::class );
		$feature_repository = $this->makeEmpty( Feature_Repository::class, [ 'get' => new Feature_Collection() ] );

		$this->handler = new Plugin_Handler(
			$resolver,
			$feature_repository,
			$this->container->get( License_Manager::class )
		);

		$this->create_test_plugin();
	}

	/**
	 * Removes the test plugin file after each test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		$this->remove_test_plugin();
		parent::tearDown();
	}

	/**
	 * Creates a dummy plugin file so get_plugins() recognizes it as installed.
	 *
	 * @return void
	 */
	private function create_test_plugin(): void {
		$plugin_dir = WP_PLUGIN_DIR . '/my-plugin';

		if ( ! is_dir( $plugin_dir ) ) {
			mkdir( $plugin_dir, 0755, true );
		}

		file_put_contents(
			$plugin_dir . '/my-plugin.php',
			"<?php\n/*\nPlugin Name: My Plugin\nVersion: 1.0.0\n*/\n"
		);

		wp_cache_delete( 'plugins', 'plugins' );
	}

	/**
	 * Removes the dummy plugin file created by create_test_plugin().
	 *
	 * @return void
	 */
	private function remove_test_plugin(): void {
		$plugin_file = WP_PLUGIN_DIR . '/my-plugin/my-plugin.php';

		if ( file_exists( $plugin_file ) ) {
			unlink( $plugin_file );
			rmdir( WP_PLUGIN_DIR . '/my-plugin' );
		}

		wp_cache_delete( 'plugins', 'plugins' );
	}

	/**
	 * Creates a Plugin_Handler with a Plugin feature in the Feature_Repository.
	 *
	 * @param mixed $check_updates_return The return value for Resolve_Update_Data::__invoke().
	 *
	 * @return Plugin_Handler
	 */
	private function handler_with_feature( $check_updates_return ): Plugin_Handler {
		$feature = new Plugin(
			[
				'slug'         => 'my-plugin',
				'group'        => 'test',
				'tier'         => 'basic',
				'name'         => 'My Plugin',
				'description'  => 'A test plugin.',
				'plugin_file'  => 'my-plugin/my-plugin.php',
				'is_available' => true,
			]
		);

		$features = new Feature_Collection();
		$features->add( $feature );

		$resolver           = $this->makeEmpty( Resolve_Update_Data::class, [ '__invoke' => $check_updates_return ] );
		$feature_repository = $this->makeEmpty( Feature_Repository::class, [ 'get' => $features ] );

		$license_manager = $this->container->get( License_Manager::class );
		$license_manager->store_key( 'LWSW-test-handler-key' );

		return new Plugin_Handler(
			$resolver,
			$feature_repository,
			$license_manager
		);
	}

	/**
	 * Tests filter_plugins_api passes through for a non-plugin_information action.
	 *
	 * @return void
	 */
	public function test_it_passes_through_for_non_plugin_information_action(): void {
		$result = $this->handler->filter_plugins_api( false, 'hot_tags', new stdClass() );

		$this->assertFalse( $result );
	}

	/**
	 * Tests filter_plugins_api passes through when slug is missing from args.
	 *
	 * @return void
	 */
	public function test_it_passes_through_for_missing_slug(): void {
		$args = new stdClass();

		$result = $this->handler->filter_plugins_api( false, 'plugin_information', $args );

		$this->assertFalse( $result );
	}

	/**
	 * Tests filter_plugins_api passes through for null action.
	 *
	 * @return void
	 */
	public function test_it_passes_through_for_null_action(): void {
		$result = $this->handler->filter_plugins_api( false, null, null );

		$this->assertFalse( $result );
	}

	/**
	 * Tests filter_plugins_api passes through when args is not an object.
	 *
	 * @return void
	 */
	public function test_it_passes_through_for_non_object_args(): void {
		$result = $this->handler->filter_plugins_api( false, 'plugin_information', 'not-an-object' );

		$this->assertFalse( $result );
	}

	/**
	 * Tests filter_plugins_api passes through when the slug is not a known feature.
	 *
	 * @return void
	 */
	public function test_it_passes_through_when_no_matching_feature(): void {
		$args       = new stdClass();
		$args->slug = 'my-plugin';

		$result = $this->handler->filter_plugins_api( false, 'plugin_information', $args );

		$this->assertFalse( $result );
	}

	/**
	 * Tests filter_plugins_api passes through for an unknown slug not in the response.
	 *
	 * @return void
	 */
	public function test_it_passes_through_for_unknown_slug(): void {
		$handler = $this->handler_with_feature( [ 'other-plugin' => [ 'version' => '2.0.0' ] ] );

		$args       = new stdClass();
		$args->slug = 'unknown-plugin';

		$result = $handler->filter_plugins_api( false, 'plugin_information', $args );

		$this->assertFalse( $result );
	}

	/**
	 * Tests filter_plugins_api passes through when the update client returns a WP_Error.
	 *
	 * @return void
	 */
	public function test_it_passes_through_when_update_client_errors(): void {
		$handler = $this->handler_with_feature( new WP_Error( 'fail', 'Error' ) );

		$args       = new stdClass();
		$args->slug = 'my-plugin';

		$result = $handler->filter_plugins_api( false, 'plugin_information', $args );

		$this->assertFalse( $result );
	}

	/**
	 * Tests filter_plugins_api returns a WP-format object for a known feature.
	 *
	 * @return void
	 */
	public function test_it_returns_wp_format_for_feature(): void {
		$update_data = [
			'my-plugin' => [
				'version'     => '2.0.0',
				'package'     => 'https://example.com/my-plugin.zip',
				'name'        => 'My Plugin',
				'plugin_file' => 'my-plugin/my-plugin.php',
			],
		];

		$handler = $this->handler_with_feature( $update_data );

		$args       = new stdClass();
		$args->slug = 'my-plugin';

		$result = $handler->filter_plugins_api( false, 'plugin_information', $args );

		$this->assertInstanceOf( stdClass::class, $result );
		$this->assertSame( 'my-plugin', $result->slug );
		$this->assertSame( '2.0.0', $result->version );
		$this->assertSame( 'My Plugin', $result->name );
		$this->assertSame( 'https://example.com/my-plugin.zip', $result->download_link );
	}

	/**
	 * Tests filter_update_check passes through for non-object transient.
	 *
	 * @return void
	 */
	public function test_filter_update_check_passes_through_for_non_object(): void {
		$result = $this->handler->filter_update_check( false );

		$this->assertInstanceOf( stdClass::class, $result );
	}

	/**
	 * Tests filter_update_check returns the transient when there are no features.
	 *
	 * @return void
	 */
	public function test_filter_update_check_returns_transient_when_no_features(): void {
		$transient           = new stdClass();
		$transient->response = [];

		$result = $this->handler->filter_update_check( $transient );

		$this->assertSame( $transient, $result );
	}

	/**
	 * Tests filter_update_check handles an update client error gracefully.
	 *
	 * @return void
	 */
	public function test_filter_update_check_handles_client_error_gracefully(): void {
		$handler = $this->handler_with_feature( new WP_Error( 'fail', 'Error' ) );

		$transient           = new stdClass();
		$transient->response = [];

		$result = $handler->filter_update_check( $transient );

		$this->assertSame( $transient, $result );
	}

	/**
	 * Tests filter_update_check adds an update to transient->response when a newer version is available.
	 *
	 * @return void
	 */
	public function test_filter_update_check_adds_to_response_when_update_available(): void {
		$update_data = [
			'my-plugin' => [
				'version'     => '2.0.0',
				'package'     => 'https://example.com/my-plugin.zip',
				'plugin_file' => 'my-plugin/my-plugin.php',
			],
		];

		$handler = $this->handler_with_feature( $update_data );

		$transient = new stdClass();

		$result = $handler->filter_update_check( $transient );

		$this->assertObjectHasProperty( 'response', $result );
		$this->assertArrayHasKey( 'my-plugin/my-plugin.php', $result->response );
		$this->assertSame( '2.0.0', $result->response['my-plugin/my-plugin.php']->new_version );
	}

	/**
	 * Tests filter_update_check adds to transient->no_update when no newer version exists.
	 *
	 * @return void
	 */
	public function test_filter_update_check_adds_to_no_update_when_no_newer_version(): void {
		$update_data = [
			'my-plugin' => [
				'version'     => '',
				'package'     => 'https://example.com/my-plugin.zip',
				'plugin_file' => 'my-plugin/my-plugin.php',
			],
		];

		$handler = $this->handler_with_feature( $update_data );

		$transient = new stdClass();

		$result = $handler->filter_update_check( $transient );

		$this->assertObjectHasProperty( 'no_update', $result );
		$this->assertArrayHasKey( 'my-plugin/my-plugin.php', $result->no_update );
		$this->assertSame( '', $result->no_update['my-plugin/my-plugin.php']->new_version );
	}

	/**
	 * Tests filter_update_check preserves an existing update from another system
	 * when our data says no newer version is available.
	 *
	 * @return void
	 */
	public function test_filter_update_check_preserves_existing_update_from_other_system(): void {
		$update_data = [
			'my-plugin' => [
				'version'     => '',
				'package'     => 'https://example.com/my-plugin.zip',
				'plugin_file' => 'my-plugin/my-plugin.php',
			],
		];

		$handler = $this->handler_with_feature( $update_data );

		$existing_update              = new stdClass();
		$existing_update->slug        = 'my-plugin';
		$existing_update->new_version = '1.5.0';
		$existing_update->package     = 'https://legacy.example.com/my-plugin.zip';

		$transient                                      = new stdClass();
		$transient->response                            = [];
		$transient->response['my-plugin/my-plugin.php'] = $existing_update;
		$transient->no_update                           = [];

		$result = $handler->filter_update_check( $transient );

		$this->assertArrayHasKey( 'my-plugin/my-plugin.php', $result->response, 'Existing update from another system should be preserved in response.' );
		$this->assertSame( $existing_update, $result->response['my-plugin/my-plugin.php'], 'The existing update object should not be modified.' );
		$this->assertArrayNotHasKey( 'my-plugin/my-plugin.php', $result->no_update, 'Plugin should not appear in no_update when it has an existing update.' );
	}
}
