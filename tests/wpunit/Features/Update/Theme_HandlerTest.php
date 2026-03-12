<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Features\Update;

use StellarWP\Uplink\Features\Update\Resolve_Update_Data;
use StellarWP\Uplink\Features\Feature_Repository;
use StellarWP\Uplink\Features\Feature_Collection;
use StellarWP\Uplink\Features\Types\Theme;
use StellarWP\Uplink\Features\Update\Theme_Handler;
use StellarWP\Uplink\Licensing\License_Manager;
use StellarWP\Uplink\Tests\UplinkTestCase;
use stdClass;
use WP_Error;

final class Theme_HandlerTest extends UplinkTestCase {

	/**
	 * The handler under test.
	 *
	 * @var Theme_Handler
	 */
	private Theme_Handler $handler;

	/**
	 * Absolute path to the temporary theme directory created for tests.
	 *
	 * @var string|null
	 */
	private $theme_dir;

	/**
	 * Sets up the handler with mocked dependencies before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$resolver           = $this->makeEmpty( Resolve_Update_Data::class );
		$feature_repository = $this->makeEmpty( Feature_Repository::class, [ 'get' => new Feature_Collection() ] );

		$this->handler = new Theme_Handler(
			$resolver,
			$feature_repository,
			$this->container->get( License_Manager::class )
		);

		$this->create_test_theme();
	}

	/**
	 * Removes the test theme directory after each test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		$this->remove_test_theme();
		parent::tearDown();
	}

	/**
	 * Creates a minimal theme directory so wp_get_themes() recognizes 'my-theme'
	 * as installed. WordPress reads theme headers from style.css on disk, so a
	 * real directory is required — there is no filter hook on wp_get_themes().
	 *
	 * @return void
	 */
	private function create_test_theme(): void {
		$this->theme_dir = get_theme_root() . '/my-theme';

		if ( ! is_dir( $this->theme_dir ) ) {
			mkdir( $this->theme_dir, 0755, true );
		}

		file_put_contents(
			$this->theme_dir . '/style.css',
			"/*\nTheme Name: My Theme\nVersion: 1.0.0\n*/\n"
		);

		// WordPress requires index.php to consider a theme valid (no errors).
		// wp_get_themes() with the default errors=>false skips themes with errors.
		file_put_contents( $this->theme_dir . '/index.php', "<?php // silence\n" );

		// Clear the static theme-directory cache so wp_get_themes() picks up the new entry.
		wp_clean_themes_cache();
	}

	/**
	 * Removes the theme directory created by create_test_theme().
	 *
	 * @return void
	 */
	private function remove_test_theme(): void {
		if ( $this->theme_dir === null ) {
			return;
		}

		$style = $this->theme_dir . '/style.css';
		$index = $this->theme_dir . '/index.php';

		if ( file_exists( $style ) ) {
			unlink( $style );
		}

		if ( file_exists( $index ) ) {
			unlink( $index );
		}

		if ( is_dir( $this->theme_dir ) ) {
			rmdir( $this->theme_dir );
		}

		wp_clean_themes_cache();

		$this->theme_dir = null;
	}

	/**
	 * Creates a Theme_Handler with a Theme feature in the Feature_Repository.
	 *
	 * @param mixed $check_updates_return The return value for Resolve_Update_Data::__invoke().
	 *
	 * @return Theme_Handler
	 */
	private function handler_with_feature( $check_updates_return ): Theme_Handler {
		$feature = new Theme(
			[
				'slug'         => 'my-theme',
				'group'        => 'test',
				'tier'         => 'basic',
				'name'         => 'My Theme',
				'description'  => 'A test theme.',
				'is_available' => true,
				'authors'      => [ 'TestAuthor' ],
			]
		);

		$features = new Feature_Collection();
		$features->add( $feature );

		$resolver           = $this->makeEmpty( Resolve_Update_Data::class, [ '__invoke' => $check_updates_return ] );
		$feature_repository = $this->makeEmpty( Feature_Repository::class, [ 'get' => $features ] );

		$license_manager = $this->container->get( License_Manager::class );
		$license_manager->store_key( 'LWSW-test-handler-key' );

		return new Theme_Handler(
			$resolver,
			$feature_repository,
			$license_manager
		);
	}

	// -------------------------------------------------------------------------
	// filter_themes_api
	// -------------------------------------------------------------------------

	/**
	 * Tests filter_themes_api passes through for a non-theme_information action.
	 *
	 * @return void
	 */
	public function test_it_passes_through_for_non_theme_information_action(): void {
		$result = $this->handler->filter_themes_api( false, 'hot_tags', new stdClass() );

		$this->assertFalse( $result );
	}

	/**
	 * Tests filter_themes_api passes through when slug is missing from args.
	 *
	 * @return void
	 */
	public function test_it_passes_through_for_missing_slug(): void {
		$args = new stdClass();

		$result = $this->handler->filter_themes_api( false, 'theme_information', $args );

		$this->assertFalse( $result );
	}

	/**
	 * Tests filter_themes_api passes through for null action.
	 *
	 * @return void
	 */
	public function test_it_passes_through_for_null_action(): void {
		$result = $this->handler->filter_themes_api( false, null, null );

		$this->assertFalse( $result );
	}

	/**
	 * Tests filter_themes_api passes through when args is not an object.
	 *
	 * @return void
	 */
	public function test_it_passes_through_for_non_object_args(): void {
		$result = $this->handler->filter_themes_api( false, 'theme_information', 'not-an-object' );

		$this->assertFalse( $result );
	}

	/**
	 * Tests filter_themes_api passes through when the slug is not a known feature.
	 *
	 * @return void
	 */
	public function test_it_passes_through_when_no_matching_feature(): void {
		$args       = new stdClass();
		$args->slug = 'my-theme';

		$result = $this->handler->filter_themes_api( false, 'theme_information', $args );

		$this->assertFalse( $result );
	}

	/**
	 * Tests filter_themes_api passes through for an unknown slug not in the response.
	 *
	 * @return void
	 */
	public function test_it_passes_through_for_unknown_slug(): void {
		$handler = $this->handler_with_feature( [ 'other-theme' => [ 'version' => '2.0.0' ] ] );

		$args       = new stdClass();
		$args->slug = 'unknown-theme';

		$result = $handler->filter_themes_api( false, 'theme_information', $args );

		$this->assertFalse( $result );
	}

	/**
	 * Tests filter_themes_api passes through when the update client returns a WP_Error.
	 *
	 * @return void
	 */
	public function test_it_passes_through_when_update_client_errors(): void {
		$handler = $this->handler_with_feature( new WP_Error( 'fail', 'Error' ) );

		$args       = new stdClass();
		$args->slug = 'my-theme';

		$result = $handler->filter_themes_api( false, 'theme_information', $args );

		$this->assertFalse( $result );
	}

	/**
	 * Tests filter_themes_api returns a WP-format object for a known feature.
	 *
	 * @return void
	 */
	public function test_it_returns_wp_format_for_feature(): void {
		$update_data = [
			'my-theme' => [
				'version' => '2.0.0',
				'package' => 'https://example.com/my-theme.zip',
				'name'    => 'My Theme',
			],
		];

		$handler = $this->handler_with_feature( $update_data );

		$args       = new stdClass();
		$args->slug = 'my-theme';

		$result = $handler->filter_themes_api( false, 'theme_information', $args );

		$this->assertInstanceOf( stdClass::class, $result );
		$this->assertSame( 'my-theme', $result->slug );
		$this->assertSame( '2.0.0', $result->version );
		$this->assertSame( 'My Theme', $result->name );
		$this->assertSame( 'https://example.com/my-theme.zip', $result->download_link );
	}

	// -------------------------------------------------------------------------
	// filter_update_check
	// -------------------------------------------------------------------------

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
			'my-theme' => [
				'version' => '2.0.0',
				'package' => 'https://example.com/my-theme.zip',
			],
		];

		$handler = $this->handler_with_feature( $update_data );

		$transient = new stdClass();

		$result = $handler->filter_update_check( $transient );

		$this->assertObjectHasProperty( 'response', $result );
		$this->assertArrayHasKey( 'my-theme', $result->response );
		$this->assertIsArray( $result->response['my-theme'], 'Theme update transient entries should be arrays, not objects.' );
		$this->assertSame( '2.0.0', $result->response['my-theme']['new_version'] );
		$this->assertSame( 'my-theme', $result->response['my-theme']['theme'] );
	}

	/**
	 * Tests filter_update_check adds to transient->no_update when no newer version exists.
	 *
	 * @return void
	 */
	public function test_filter_update_check_adds_to_no_update_when_no_newer_version(): void {
		$update_data = [
			'my-theme' => [
				'version' => '',
				'package' => 'https://example.com/my-theme.zip',
			],
		];

		$handler = $this->handler_with_feature( $update_data );

		$transient = new stdClass();

		$result = $handler->filter_update_check( $transient );

		$this->assertObjectHasProperty( 'no_update', $result );
		$this->assertArrayHasKey( 'my-theme', $result->no_update );
		$this->assertIsArray( $result->no_update['my-theme'], 'Theme no_update transient entries should be arrays, not objects.' );
		$this->assertSame( '', $result->no_update['my-theme']['new_version'] );
	}

	/**
	 * Tests filter_update_check preserves an existing update from another system
	 * when our data says no newer version is available.
	 *
	 * @return void
	 */
	public function test_filter_update_check_preserves_existing_update_from_other_system(): void {
		$update_data = [
			'my-theme' => [
				'version' => '',
				'package' => 'https://example.com/my-theme.zip',
			],
		];

		$handler = $this->handler_with_feature( $update_data );

		$existing_update = [
			'theme'       => 'my-theme',
			'new_version' => '1.5.0',
			'package'     => 'https://legacy.example.com/my-theme.zip',
		];

		$transient                       = new stdClass();
		$transient->response             = [];
		$transient->response['my-theme'] = $existing_update;
		$transient->no_update            = [];

		$result = $handler->filter_update_check( $transient );

		$this->assertArrayHasKey( 'my-theme', $result->response, 'Existing update from another system should be preserved in response.' );
		$this->assertSame( $existing_update, $result->response['my-theme'], 'The existing update array should not be modified.' );
		$this->assertArrayNotHasKey( 'my-theme', $result->no_update, 'Theme should not appear in no_update when it has an existing update.' );
	}
}
