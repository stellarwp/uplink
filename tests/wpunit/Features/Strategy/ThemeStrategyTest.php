<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Features\Strategy;

use StellarWP\Uplink\Features\Error_Code;
use StellarWP\Uplink\Features\Strategy\Theme_Strategy;
use StellarWP\Uplink\Features\Types\Theme;
use StellarWP\Uplink\Tests\UplinkTestCase;
use WP_Error;

/**
 * Tests for the Theme_Strategy feature-gating strategy.
 *
 * These tests exercise the strategy's logic against real WordPress state
 * (theme directories, transients) via the WPLoader module.
 *
 * Theme enable = install only (no switch_theme).
 * Theme disable = no-op (theme files are never deleted).
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

		$this->feature             = $this->make_theme_feature();
		$this->strategy            = new Theme_Strategy( $this->feature );
		$this->original_stylesheet = get_option( 'stylesheet', '' );
	}

	protected function tearDown(): void {
		// Clean up locks.
		delete_transient( 'stellarwp_uplink_install_lock' );

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
		set_transient( 'stellarwp_uplink_install_lock', '1', 120 );

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
		$this->assertFalse( get_transient( 'stellarwp_uplink_install_lock' ) );
		// The active theme should NOT have changed.
		$this->assertSame( $this->original_stylesheet, get_option( 'stylesheet' ) );
	}

	// -------------------------------------------------------------------------
	// disable() tests
	// -------------------------------------------------------------------------

	/**
	 * disable() always succeeds. Theme files are never deleted.
	 */
	public function test_disable_succeeds(): void {
		$this->install_test_theme( self::STYLESHEET, 'StellarWP' );

		$result = $this->strategy->disable();

		$this->assertTrue( $result );
	}

	/**
	 * disable() succeeds even when the theme is the active WordPress theme.
	 */
	public function test_disable_succeeds_even_when_theme_is_active_wp_theme(): void {
		$this->install_test_theme( self::STYLESHEET, 'StellarWP' );
		$this->mock_active_theme( self::STYLESHEET );

		$result = $this->strategy->disable();

		$this->assertTrue( $result );
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
				'group'        => 'Test',
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
