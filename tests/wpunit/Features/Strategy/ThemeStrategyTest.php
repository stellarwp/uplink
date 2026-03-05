<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Features\Strategy;

use StellarWP\Uplink\Features\Error_Code;
use StellarWP\Uplink\Features\Strategy\Theme_Strategy;
use StellarWP\Uplink\Features\Types\Feature;
use StellarWP\Uplink\Features\Types\Theme;
use StellarWP\Uplink\Tests\UplinkTestCase;
use WP_Error;
use WP_Theme;

/**
 * Tests for the Theme_Strategy feature-gating strategy.
 *
 * These tests exercise the strategy's logic against real WordPress state
 * (theme directories, wp_options, transients) via the WPLoader module.
 *
 * Theme enable = install only (no switch_theme).
 * Theme disable = update stored state to false.
 * Theme is_active = installed on disk.
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
	 * The option key for the test feature's stored state.
	 *
	 * @var string
	 */
	private const OPTION_KEY = 'stellarwp_uplink_feature_test-theme-feature_active';

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

		$this->strategy            = new Theme_Strategy();
		$this->feature             = $this->make_theme_feature();
		$this->original_stylesheet = get_option( 'stylesheet', '' );
	}

	protected function tearDown(): void {
		// Clean up stored state and locks.
		delete_option( self::OPTION_KEY );
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
	 * enable() must reject non-Theme instances with a type mismatch error.
	 */
	public function test_enable_returns_type_mismatch_error_for_non_theme_feature(): void {
		$non_theme = $this->create_non_theme_feature();

		$result = $this->strategy->enable( $non_theme );

		$this->assertWPError( $result );
		$this->assertSame( Error_Code::FEATURE_TYPE_MISMATCH, $result->get_error_code() );
	}

	/**
	 * enable() on an already-installed theme should return true and update stored state,
	 * without switching the active theme.
	 */
	public function test_enable_returns_true_when_theme_already_installed(): void {
		$this->install_test_theme( self::STYLESHEET, 'StellarWP' );

		$result = $this->strategy->enable( $this->feature );

		$this->assertTrue( $result );
		$this->assertSame( '1', get_option( self::OPTION_KEY ) );
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
			$result = $this->strategy->enable( $this->feature );

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
			$result = $this->strategy->enable( $this->feature );

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

		$result = $this->strategy->enable( $this->feature );

		$this->assertWPError( $result );
		$this->assertSame( Error_Code::INSTALL_LOCKED, $result->get_error_code() );
	}

	/**
	 * enable() skips installation when the theme is already installed,
	 * updates stored state, but does NOT switch the active theme.
	 */
	public function test_enable_skips_install_for_already_installed_theme(): void {
		$this->install_test_theme( self::STYLESHEET, 'StellarWP' );

		$result = $this->strategy->enable( $this->feature );

		$this->assertTrue( $result );
		$this->assertSame( '1', get_option( self::OPTION_KEY ) );
		$this->assertFalse( get_transient( 'stellarwp_uplink_install_lock' ) );
		// The active theme should NOT have changed.
		$this->assertSame( $this->original_stylesheet, get_option( 'stylesheet' ) );
	}

	// -------------------------------------------------------------------------
	// disable() tests
	// -------------------------------------------------------------------------

	/**
	 * disable() must reject non-Theme instances with a type mismatch error.
	 */
	public function test_disable_returns_type_mismatch_error_for_non_theme_feature(): void {
		$non_theme = $this->create_non_theme_feature();

		$result = $this->strategy->disable( $non_theme );

		$this->assertWPError( $result );
		$this->assertSame( Error_Code::FEATURE_TYPE_MISMATCH, $result->get_error_code() );
	}

	/**
	 * disable() always succeeds and updates stored state to false.
	 * Themes are never "deactivated" — disable only updates stored state.
	 */
	public function test_disable_succeeds_and_updates_stored_state(): void {
		$this->install_test_theme( self::STYLESHEET, 'StellarWP' );
		update_option( self::OPTION_KEY, '1', true );

		$result = $this->strategy->disable( $this->feature );

		$this->assertTrue( $result );
		$this->assertSame( '0', get_option( self::OPTION_KEY ) );
	}

	/**
	 * disable() succeeds even when the theme is the active WordPress theme.
	 * Unlike the old behavior, we no longer block disable for active themes.
	 */
	public function test_disable_succeeds_even_when_theme_is_active_wp_theme(): void {
		$this->install_test_theme( self::STYLESHEET, 'StellarWP' );
		$this->mock_active_theme( self::STYLESHEET );
		update_option( self::OPTION_KEY, '1', true );

		$result = $this->strategy->disable( $this->feature );

		$this->assertTrue( $result );
		$this->assertSame( '0', get_option( self::OPTION_KEY ) );
	}

	// -------------------------------------------------------------------------
	// is_active() tests
	// -------------------------------------------------------------------------

	/**
	 * is_active() returns false for non-Theme instances.
	 */
	public function test_is_active_returns_false_for_non_theme_feature(): void {
		$non_theme = $this->create_non_theme_feature();

		$this->assertFalse( $this->strategy->is_active( $non_theme ) );
	}

	/**
	 * is_active() returns true when the theme is installed on disk.
	 * "Active" for themes means "installed and available", not "currently switched to".
	 */
	public function test_is_active_returns_true_when_theme_is_installed(): void {
		$this->install_test_theme( self::STYLESHEET, 'StellarWP' );

		$this->assertTrue( $this->strategy->is_active( $this->feature ) );
	}

	/**
	 * is_active() returns false when the theme is not installed on disk.
	 */
	public function test_is_active_returns_false_when_theme_is_not_installed(): void {
		$this->assertFalse( $this->strategy->is_active( $this->feature ) );
	}

	/**
	 * is_active() self-heals a stale stored state of true when the theme has
	 * been manually deleted from disk.
	 */
	public function test_is_active_self_heals_stale_true_to_false(): void {
		update_option( self::OPTION_KEY, '1', true );

		// Theme is NOT installed on disk — stored state should heal to false.
		$result = $this->strategy->is_active( $this->feature );

		$this->assertFalse( $result );
		$this->assertSame( '0', get_option( self::OPTION_KEY ) );
	}

	/**
	 * is_active() self-heals a stale stored state of false when the theme is
	 * actually installed on disk.
	 */
	public function test_is_active_self_heals_stale_false_to_true(): void {
		update_option( self::OPTION_KEY, '0', true );
		$this->install_test_theme( self::STYLESHEET, 'StellarWP' );

		$result = $this->strategy->is_active( $this->feature );

		$this->assertTrue( $result );
		$this->assertSame( '1', get_option( self::OPTION_KEY ) );
	}

	/**
	 * is_active() writes the correct stored state when no option exists yet.
	 */
	public function test_is_active_initializes_stored_state_when_missing(): void {
		$this->assertFalse( get_option( self::OPTION_KEY, false ) );

		$this->strategy->is_active( $this->feature );

		$this->assertSame( '0', get_option( self::OPTION_KEY ) );
	}

	// -------------------------------------------------------------------------
	// Sync hook tests
	// -------------------------------------------------------------------------

	/**
	 * on_theme_switch updates stored state for both old and new themes.
	 */
	public function test_on_theme_switch_updates_state_for_known_features(): void {
		$old_stylesheet = 'old-theme-feature';
		$new_stylesheet = self::STYLESHEET;

		$old_feature = $this->make_theme_feature( 'old-theme-feature', $old_stylesheet );
		$new_feature = $this->make_theme_feature( 'test-theme-feature', $new_stylesheet );

		update_option( 'stellarwp_uplink_feature_old-theme-feature_active', '1', true );

		$strategy = new Theme_Strategy(
			function ( string $stylesheet ) use ( $old_feature, $new_feature ): ?Theme {
				if ( $stylesheet === $old_feature->get_wp_identifier() ) {
					return $old_feature;
				}
				if ( $stylesheet === $new_feature->get_wp_identifier() ) {
					return $new_feature;
				}
				return null;
			}
		);

		$old_theme = new WP_Theme( $old_stylesheet, '' );
		$new_theme = new WP_Theme( $new_stylesheet, '' );

		$strategy->on_theme_switch( 'Test Theme', $new_theme, $old_theme );

		$this->assertSame( '0', get_option( 'stellarwp_uplink_feature_old-theme-feature_active' ) );
		$this->assertSame( '1', get_option( self::OPTION_KEY ) );
	}

	/**
	 * on_theme_switch ignores unknown themes (resolver returns null).
	 */
	public function test_on_theme_switch_ignores_unknown_themes(): void {
		$strategy = new Theme_Strategy(
			function ( string $stylesheet ): ?Theme {
				return null;
			}
		);

		$old_theme = new WP_Theme( 'unknown-old', '' );
		$new_theme = new WP_Theme( 'unknown-new', '' );

		$strategy->on_theme_switch( 'Unknown', $new_theme, $old_theme );

		$this->assertFalse( get_option( self::OPTION_KEY, false ) );
	}

	/**
	 * on_theme_switch is a no-op when no feature_resolver is configured.
	 */
	public function test_on_theme_switch_noops_without_resolver(): void {
		$old_theme = new WP_Theme( 'some-old', '' );
		$new_theme = new WP_Theme( self::STYLESHEET, '' );

		$this->strategy->on_theme_switch( 'Test', $new_theme, $old_theme );

		$this->assertFalse( get_option( self::OPTION_KEY, false ) );
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

		$feature = $this->make_theme_feature( 'test-theme-feature', self::STYLESHEET, [ 'StellarWP' ] );
		$result  = $this->strategy->enable( $feature );

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

		$feature = $this->make_theme_feature( 'test-theme-feature', self::STYLESHEET, [ 'StellarWP' ] );
		$result  = $this->strategy->enable( $feature );

		$this->assertTrue( $result );
		$this->assertSame( '1', get_option( self::OPTION_KEY ) );
		// The active theme should NOT have changed.
		$this->assertSame( $this->original_stylesheet, get_option( 'stylesheet' ) );
	}

	/**
	 * enable() skips the ownership check when the feature has an empty
	 * authors array.
	 */
	public function test_enable_skips_ownership_check_when_authors_is_empty(): void {
		$this->install_test_theme( self::STYLESHEET, 'Foreign Developer' );

		$feature = $this->make_theme_feature( 'test-theme-feature', self::STYLESHEET, [] );
		$result  = $this->strategy->enable( $feature );

		$this->assertTrue( $result );
		$this->assertSame( '1', get_option( self::OPTION_KEY ) );
		// The active theme should NOT have changed.
		$this->assertSame( $this->original_stylesheet, get_option( 'stylesheet' ) );
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Create a standard Theme feature for testing.
	 *
	 * @param string   $slug       Feature slug.
	 * @param string   $stylesheet Theme stylesheet.
	 * @param string[] $authors    Expected theme authors.
	 *
	 * @return Theme
	 */
	private function make_theme_feature(
		string $slug = 'test-theme-feature',
		string $stylesheet = self::STYLESHEET,
		array $authors = [ 'StellarWP' ]
	): Theme {
		return new Theme(
			[
				'slug'         => $slug,
				'group'        => 'Test',
				'tier'         => 'Tier 1',
				'name'         => 'Test Theme Feature',
				'description'  => 'A test theme for unit tests.',
				'wp_identifier' => $stylesheet,
				'is_available' => true,
				'authors'      => $authors,
			]
		);
	}

	/**
	 * Create a non-Theme Feature subclass for type-guard testing.
	 *
	 * @return Feature
	 */
	private function create_non_theme_feature(): Feature {
		return new class( [
			'slug'         => 'non-theme',
			'group'        => 'Test',
			'tier'         => 'Tier 1',
			'name'         => 'Non-Theme Feature',
			'description'  => 'Not a theme.',
			'type'         => 'other',
			'is_available' => true,
		] ) extends Feature {

			/**
			 * @inheritDoc
			 */
			public static function from_array( array $data ) {
				return new self( $data );
			}
		};
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
