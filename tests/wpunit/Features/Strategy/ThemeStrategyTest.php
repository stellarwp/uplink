<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Features\Strategy;

use StellarWP\Uplink\Features\Error_Code;
use StellarWP\Uplink\Features\Strategy\Theme_Strategy;
use StellarWP\Uplink\Features\Types\Theme;
use StellarWP\Uplink\Tests\UplinkTestCase;
use WP_Error;
use WP_Upgrader;

/**
 * Tests for the Theme_Strategy feature-gating strategy.
 *
 * These tests exercise the strategy's logic against real WordPress state
 * (theme directories, WP_Upgrader locks) via the WPLoader module.
 *
 * Theme enable = install only (no switch_theme).
 * Theme disable = error if on disk (user must delete manually), success if not on disk.
 * Theme is_active = installed on disk.
 *
 * Theme features derive their active state from disk presence. No DB option
 * is stored.
 *
 * @see Theme_Strategy
 */
final class ThemeStrategyTest extends UplinkTestCase {

	/**
	 * Test stylesheet used across tests.
	 *
	 * @var string
	 */
	private const STYLESHEET = 'test-theme-feature';

	/**
	 * @var Theme_Strategy
	 */
	private $strategy;

	/**
	 * @var Theme
	 */
	private $feature;

	/**
	 * The original stylesheet before test manipulation.
	 *
	 * @var string
	 */
	private string $original_stylesheet;

	protected function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'WP_Upgrader' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}

		$this->feature             = $this->make_theme_feature();
		$this->strategy            = new Theme_Strategy( $this->feature );
		$this->original_stylesheet = get_option( 'stylesheet', '' );
	}

	protected function tearDown(): void {
		// Clean up locks.
		WP_Upgrader::release_lock( 'stellarwp_uplink_install_lock' );

		// Restore original active theme.
		update_option( 'stylesheet', $this->original_stylesheet );

		// Remove test theme from themes directory if present.
		$this->remove_test_theme( self::STYLESHEET );

		parent::tearDown();
	}

	// -------------------------------------------------------------------------
	// enable() tests
	// -------------------------------------------------------------------------

	/**
	 * enable() on an already-installed theme should return true
	 * without switching the active theme.
	 */
	public function test_enable_returns_true_when_theme_already_installed(): void {
		$this->install_test_theme( self::STYLESHEET, 'StellarWP' );

		$result = $this->strategy->enable();

		$this->assertTrue( $result );
		// The active theme should NOT have changed.
		$this->assertSame( $this->original_stylesheet, get_option( 'stylesheet' ) );
	}

	/**
	 * enable() returns a themes_api_failed error when themes_api()
	 * returns a WP_Error for the feature slug.
	 */
	public function test_enable_returns_themes_api_failed_error(): void {
		$filter = static function ( $result, $action, $args ) {
			if ( 'theme_information' === $action && $args->slug === self::STYLESHEET ) {
				return new WP_Error( 'themes_api_failed', 'No info available.' );
			}
			return $result;
		};
		add_filter( 'themes_api', $filter, 10, 3 );

		try {
			$result = $this->strategy->enable();

			$this->assertWPError( $result );
			$this->assertSame( Error_Code::THEMES_API_FAILED, $result->get_error_code() );
		} finally {
			remove_filter( 'themes_api', $filter, 10 );
		}
	}

	/**
	 * enable() returns a download_link_empty error when themes_api()
	 * returns an object without a download_link property.
	 */
	public function test_enable_returns_download_link_empty_error(): void {
		$filter = static function ( $result, $action, $args ) {
			if ( 'theme_information' === $action && $args->slug === self::STYLESHEET ) {
				return (object) [ 'slug' => self::STYLESHEET ];
			}
			return $result;
		};
		add_filter( 'themes_api', $filter, 10, 3 );

		try {
			$result = $this->strategy->enable();

			$this->assertWPError( $result );
			$this->assertSame( Error_Code::DOWNLOAD_LINK_MISSING, $result->get_error_code() );
		} finally {
			remove_filter( 'themes_api', $filter, 10 );
		}
	}

	/**
	 * enable() returns an install_locked error when another install is already
	 * in progress (global lock).
	 */
	public function test_enable_returns_install_locked_error_when_concurrent_install_in_progress(): void {
		WP_Upgrader::create_lock( 'stellarwp_uplink_install_lock', 120 );

		$result = $this->strategy->enable();

		$this->assertWPError( $result );
		$this->assertSame( Error_Code::INSTALL_LOCKED, $result->get_error_code() );
	}

	/**
	 * enable() proceeds when a stale lock has expired past the TTL.
	 *
	 * Simulates a process that crashed without releasing the lock by writing
	 * an option timestamp older than the TTL. The next enable() should
	 * reclaim the expired lock and proceed normally.
	 */
	public function test_enable_proceeds_when_stale_lock_has_expired(): void {
		$this->install_test_theme( self::STYLESHEET, 'StellarWP' );

		// Simulate a stale lock from 5 minutes ago (TTL is 2 minutes).
		update_option( 'stellarwp_uplink_install_lock.lock', time() - 300, false );

		$result = $this->strategy->enable();

		$this->assertTrue( $result );
		$this->assertSame( $this->original_stylesheet, get_option( 'stylesheet' ) );
	}

	/**
	 * enable() is blocked by a lock that is still within the TTL window.
	 *
	 * Writes an option timestamp just 10 seconds ago — well within the
	 * 2-minute TTL — to verify the boundary is respected.
	 */
	public function test_enable_blocked_by_fresh_lock_within_ttl(): void {
		// Lock acquired 10 seconds ago — still valid.
		update_option( 'stellarwp_uplink_install_lock.lock', time() - 10, false );

		$result = $this->strategy->enable();

		$this->assertWPError( $result );
		$this->assertSame( Error_Code::INSTALL_LOCKED, $result->get_error_code() );
	}

	/**
	 * enable() skips installation when the theme is already installed
	 * and does NOT switch the active theme.
	 */
	public function test_enable_skips_install_for_already_installed_theme(): void {
		$this->install_test_theme( self::STYLESHEET, 'StellarWP' );

		$result = $this->strategy->enable();

		$this->assertTrue( $result );
		$this->assertFalse( get_option( 'stellarwp_uplink_install_lock.lock' ) );
		// The active theme should NOT have changed.
		$this->assertSame( $this->original_stylesheet, get_option( 'stylesheet' ) );
	}

	// -------------------------------------------------------------------------
	// disable() tests
	// -------------------------------------------------------------------------

	/**
	 * disable() returns success when the theme is not on disk (already "disabled").
	 */
	public function test_disable_succeeds_when_theme_is_not_installed(): void {
		$result = $this->strategy->disable();

		$this->assertTrue( $result );
	}

	/**
	 * disable() returns a THEME_DELETE_REQUIRED error when the theme is installed on disk.
	 */
	public function test_disable_returns_delete_required_when_theme_is_installed(): void {
		$this->install_test_theme( self::STYLESHEET, 'StellarWP' );

		$result = $this->strategy->disable();

		$this->assertWPError( $result );
		$this->assertSame( Error_Code::THEME_DELETE_REQUIRED, $result->get_error_code() );
	}

	/**
	 * disable() returns a THEME_DELETE_REQUIRED error even when the theme is
	 * the active WordPress theme.
	 */
	public function test_disable_returns_delete_required_when_theme_is_active_wp_theme(): void {
		$this->install_test_theme( self::STYLESHEET, 'StellarWP' );
		$this->mock_active_theme( self::STYLESHEET );

		$result = $this->strategy->disable();

		$this->assertWPError( $result );
		$this->assertSame( Error_Code::THEME_DELETE_REQUIRED, $result->get_error_code() );
	}

	// -------------------------------------------------------------------------
	// is_active() tests
	// -------------------------------------------------------------------------

	/**
	 * is_active() returns true when the theme is installed on disk.
	 */
	public function test_is_active_returns_true_when_theme_is_installed(): void {
		$this->install_test_theme( self::STYLESHEET, 'StellarWP' );

		$this->assertTrue( $this->strategy->is_active() );
	}

	/**
	 * is_active() returns false when the theme is not installed on disk.
	 */
	public function test_is_active_returns_false_when_theme_is_not_installed(): void {
		$this->assertFalse( $this->strategy->is_active() );
	}

	/**
	 * is_active() does not write any DB option.
	 */
	public function test_is_active_does_not_write_stored_state(): void {
		$this->install_test_theme( self::STYLESHEET, 'StellarWP' );

		$this->strategy->is_active();

		$this->assertFalse( get_option( 'stellarwp_uplink_feature_test-theme-feature_active', false ) );
	}

	// -------------------------------------------------------------------------
	// Ownership verification tests
	// -------------------------------------------------------------------------

	/**
	 * enable() returns a theme_ownership_mismatch error when an installed
	 * theme has a different Author header than expected.
	 */
	public function test_enable_returns_ownership_mismatch_for_installed_theme_with_wrong_author(): void {
		$this->install_test_theme( self::STYLESHEET, 'Foreign Developer' );

		$feature  = $this->make_theme_feature( self::STYLESHEET, [ 'StellarWP' ] );
		$strategy = new Theme_Strategy( $feature );
		$result   = $strategy->enable();

		$this->assertWPError( $result );
		$this->assertSame( Error_Code::THEME_OWNERSHIP_MISMATCH, $result->get_error_code() );
		$this->assertStringContainsString( 'Foreign Developer', $result->get_error_message() );
	}

	/**
	 * enable() succeeds when the installed theme's Author header matches.
	 * Theme should be installed but NOT switched to.
	 */
	public function test_enable_succeeds_when_installed_theme_has_matching_author(): void {
		$this->install_test_theme( self::STYLESHEET, 'StellarWP' );

		$feature  = $this->make_theme_feature( self::STYLESHEET, [ 'StellarWP' ] );
		$strategy = new Theme_Strategy( $feature );
		$result   = $strategy->enable();

		$this->assertTrue( $result );
		// The active theme should NOT have changed.
		$this->assertSame( $this->original_stylesheet, get_option( 'stylesheet' ) );
	}

	/**
	 * enable() skips the ownership check when the feature has an empty
	 * authors array.
	 */
	public function test_enable_skips_ownership_check_when_authors_is_empty(): void {
		$this->install_test_theme( self::STYLESHEET, 'Foreign Developer' );

		$feature  = $this->make_theme_feature( self::STYLESHEET, [] );
		$strategy = new Theme_Strategy( $feature );
		$result   = $strategy->enable();

		$this->assertTrue( $result );
		// The active theme should NOT have changed.
		$this->assertSame( $this->original_stylesheet, get_option( 'stylesheet' ) );
	}

	// -------------------------------------------------------------------------
	// Install fatal error tests
	// -------------------------------------------------------------------------

	/**
	 * enable() returns INSTALL_FAILED when Theme_Upgrader::install() throws
	 * a fatal error (Throwable), and releases the install lock afterward.
	 *
	 * Simulates a catastrophic failure during the download/unpack phase by
	 * hooking into the HTTP layer to throw an exception.
	 */
	public function test_enable_returns_install_failed_when_upgrader_install_throws(): void {
		// Make themes_api() return a valid download link.
		$api_filter = static function ( $result, $action, $args ) {
			if ( 'theme_information' === $action && $args->slug === self::STYLESHEET ) {
				return (object) [
					'slug'          => self::STYLESHEET,
					'download_link' => 'https://example.com/test-theme.zip',
				];
			}
			return $result;
		};
		add_filter( 'themes_api', $api_filter, 10, 3 );

		// Force the HTTP request to throw a Throwable during download.
		$http_filter = static function ( $response, $parsed_args, $url ) {
			if ( strpos( $url, 'example.com/test-theme.zip' ) !== false ) {
				throw new \RuntimeException( 'Simulated fatal during theme download.' );
			}
			return $response;
		};
		add_filter( 'pre_http_request', $http_filter, 10, 3 );

		try {
			$ob_level = ob_get_level();
			$result   = $this->strategy->enable();

			while ( ob_get_level() > $ob_level ) {
				ob_end_clean();
			}

			$this->assertWPError( $result );
			$this->assertSame( Error_Code::INSTALL_FAILED, $result->get_error_code() );
			$this->assertStringContainsString( 'fatal error occurred', $result->get_error_message() );
			// Exception details must not leak.
			$this->assertStringNotContainsString( 'Simulated fatal', $result->get_error_message() );
			// Lock should be released even after a fatal throw.
			$this->assertFalse( get_option( 'stellarwp_uplink_install_lock.lock' ) );
		} finally {
			remove_filter( 'themes_api', $api_filter, 10 );
			remove_filter( 'pre_http_request', $http_filter, 10 );
		}
	}

	// -------------------------------------------------------------------------
	// update() tests
	// -------------------------------------------------------------------------

	/**
	 * update() returns FEATURE_NOT_ACTIVE when the theme is not installed.
	 */
	public function test_update_returns_not_active_when_theme_not_installed(): void {
		$result = $this->strategy->update();

		$this->assertWPError( $result );
		$this->assertSame( Error_Code::FEATURE_NOT_ACTIVE, $result->get_error_code() );
	}

	/**
	 * update() returns NO_UPDATE_AVAILABLE when the theme is installed but
	 * the WordPress update transient has no update for this theme.
	 */
	public function test_update_returns_no_update_available_when_transient_empty(): void {
		$this->install_test_theme( self::STYLESHEET, 'StellarWP' );

		$result = $this->strategy->update();

		$this->assertWPError( $result );
		$this->assertSame( Error_Code::NO_UPDATE_AVAILABLE, $result->get_error_code() );
	}

	/**
	 * update() returns INSTALL_LOCKED when the global lock is held.
	 */
	public function test_update_returns_install_locked_when_lock_held(): void {
		$this->install_test_theme( self::STYLESHEET, 'StellarWP' );

		// Seed the update transient.
		set_site_transient(
			'update_themes',
			(object) [
				'response' => [
					self::STYLESHEET => [
						'theme'       => self::STYLESHEET,
						'new_version' => '2.0.0',
						'package'     => 'https://example.com/test-theme-2.0.0.zip',
					],
				],
			]
		);

		WP_Upgrader::create_lock( 'stellarwp_uplink_install_lock', 120 );

		try {
			$result = $this->strategy->update();

			$this->assertWPError( $result );
			$this->assertSame( Error_Code::INSTALL_LOCKED, $result->get_error_code() );
		} finally {
			delete_site_transient( 'update_themes' );
		}
	}

	/**
	 * update() returns THEME_OWNERSHIP_MISMATCH when the theme belongs to
	 * a different developer.
	 */
	public function test_update_returns_ownership_mismatch(): void {
		$this->install_test_theme( self::STYLESHEET, 'Foreign Developer' );

		$feature  = $this->make_theme_feature( self::STYLESHEET, [ 'StellarWP' ] );
		$strategy = new Theme_Strategy( $feature );
		$result   = $strategy->update();

		$this->assertWPError( $result );
		$this->assertSame( Error_Code::THEME_OWNERSHIP_MISMATCH, $result->get_error_code() );
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Create a standard Theme feature for testing.
	 *
	 * For themes, the slug is what WordPress uses for installation and activation.
	 *
	 * @param string   $slug    Feature slug (also the theme stylesheet).
	 * @param string[] $authors Expected theme authors.
	 *
	 * @return Theme
	 */
	private function make_theme_feature(
		string $slug = self::STYLESHEET,
		array $authors = [ 'StellarWP' ]
	): Theme {
		return new Theme(
			[
				'slug'         => $slug,
				'product'        => 'Test',
				'tier'         => 'Tier 1',
				'name'         => 'Test Theme Feature',
				'description'  => 'A test theme for unit tests.',
				'is_available' => true,
				'authors'      => $authors,
			]
		);
	}

	/**
	 * Install a minimal test theme in the themes directory.
	 *
	 * @param string $stylesheet Theme directory name.
	 * @param string $author     Theme Author header value.
	 *
	 * @return void
	 */
	private function install_test_theme( string $stylesheet, string $author ): void {
		$theme_dir = get_theme_root() . '/' . $stylesheet;

		if ( ! is_dir( $theme_dir ) ) {
			mkdir( $theme_dir, 0755, true );
		}

		file_put_contents(
			$theme_dir . '/style.css',
			"/*\nTheme Name: Test Theme Feature\nAuthor: {$author}\nVersion: 1.0.0\n*/\n"
		);

		file_put_contents(
			$theme_dir . '/index.php',
			"<?php\n// Minimal theme template.\n"
		);

		// Clear WP's theme cache so wp_get_theme() picks up the new theme.
		wp_clean_themes_cache();
	}

	/**
	 * Remove a test theme from the themes directory.
	 *
	 * @param string $stylesheet Theme directory name.
	 *
	 * @return void
	 */
	private function remove_test_theme( string $stylesheet ): void {
		$theme_dir = get_theme_root() . '/' . $stylesheet;

		if ( ! is_dir( $theme_dir ) ) {
			return;
		}

		$files = glob( $theme_dir . '/*' );

		if ( $files !== false ) {
			foreach ( $files as $file ) {
				if ( is_file( $file ) ) {
					unlink( $file );
				}
			}
		}

		rmdir( $theme_dir );

		wp_clean_themes_cache();
	}

	/**
	 * Mock the active theme by updating the stylesheet option.
	 *
	 * @param string $stylesheet Theme stylesheet (directory name).
	 *
	 * @return void
	 */
	private function mock_active_theme( string $stylesheet ): void {
		update_option( 'stylesheet', $stylesheet );
	}
}
