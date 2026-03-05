<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Features\Update;

use StellarWP\Uplink\Features\API\Update_Client;
use StellarWP\Uplink\Features\Feature_Repository;
use StellarWP\Uplink\Features\Feature_Collection;
use StellarWP\Uplink\Features\Types\Plugin;
use StellarWP\Uplink\Features\Update\Handler;
use StellarWP\Uplink\Site\Data;
use StellarWP\Uplink\Tests\UplinkTestCase;
use stdClass;
use WP_Error;

final class HandlerTest extends UplinkTestCase {

	/**
	 * The handler under test.
	 *
	 * @var Handler
	 */
	private Handler $handler;

	/**
	 * Sets up the handler with mocked dependencies before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$update_client      = $this->makeEmpty( Update_Client::class );
		$feature_repository = $this->makeEmpty( Feature_Repository::class, [ 'get' => new Feature_Collection() ] );
		$site_data          = $this->makeEmpty( Data::class, [ 'get_domain' => 'example.com' ] );

		$this->handler = new Handler(
			$update_client,
			$feature_repository,
			$site_data,
			'test-key'
		);
	}

	/**
	 * Creates a Handler with a Plugin feature in the Feature_Repository.
	 *
	 * @param mixed $check_updates_return The return value for Update_Client::check_updates().
	 *
	 * @return Handler
	 */
	private function handler_with_feature( $check_updates_return ): Handler {
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

		$update_client      = $this->makeEmpty( Update_Client::class, [ 'check_updates' => $check_updates_return ] );
		$feature_repository = $this->makeEmpty( Feature_Repository::class, [ 'get' => $features ] );

		return new Handler(
			$update_client,
			$feature_repository,
			$this->makeEmpty( Data::class, [ 'get_domain' => 'example.com' ] ),
			'test-key'
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
		$handler = $this->handler_with_feature( [ 'other-plugin' => [ 'new_version' => '2.0.0' ] ] );

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
				'new_version' => '2.0.0',
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

		$this->assertFalse( $result );
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
				'new_version' => '2.0.0',
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
				'new_version' => '',
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
}
