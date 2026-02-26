<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Features\Strategy;

use StellarWP\Uplink\Features\Strategy\Zip_Strategy;
use StellarWP\Uplink\Features\Types\Feature;
use StellarWP\Uplink\Features\Types\Zip;
use StellarWP\Uplink\Tests\UplinkTestCase;
use WP_Error;

/**
 * Tests for the Zip_Strategy feature-gating strategy.
 *
 * These tests exercise the strategy's logic against real WordPress state
 * (active_plugins option, wp_options, transients) via the WPLoader module.
 * Plugin installation is not tested here — it requires actual filesystem and
 * HTTP operations better suited to integration tests with a real ZIP URL.
 *
 * @see Zip_Strategy
 */
final class ZipStrategyTest extends UplinkTestCase {

	/**
	 * Test plugin file path used across tests.
	 *
	 * @var string
	 */
	private const PLUGIN_FILE = 'test-feature/test-feature.php';

	/**
	 * The option key for the test feature's stored state.
	 *
	 * @var string
	 */
	private const OPTION_KEY = 'stellarwp_uplink_feature_test-feature_active';

	/**
	 * The transient key for the test feature's install lock.
	 *
	 * @var string
	 */
	private const LOCK_KEY = 'stellarwp_uplink_install_lock_test-feature';

	/**
	 * @var Zip_Strategy
	 */
	private $strategy;

	/**
	 * @var Zip
	 */
	private $feature;

	protected function setUp(): void {
		parent::setUp();

		// Load plugin.php so is_plugin_active() etc. are available.
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		$this->strategy = new Zip_Strategy();
		$this->feature  = $this->make_zip_feature();
	}

	protected function tearDown(): void {
		// Clean up stored state and locks.
		delete_option( self::OPTION_KEY );
		delete_transient( self::LOCK_KEY );

		// Ensure the test plugin is deactivated.
		$active = get_option( 'active_plugins', [] );
		$active = array_diff( $active, [ self::PLUGIN_FILE ] );
		update_option( 'active_plugins', array_values( $active ) );

		parent::tearDown();
	}

	// -------------------------------------------------------------------------
	// enable() tests
	// -------------------------------------------------------------------------

	/**
	 * enable() must reject non-Zip instances with a type mismatch error.
	 */
	public function test_enable_returns_type_mismatch_error_for_non_zip_feature(): void {
		$non_zip = $this->create_non_zip_feature();

		$result = $this->strategy->enable( $non_zip );

		$this->assertWPError( $result );
		$this->assertSame( 'feature_type_mismatch', $result->get_error_code() );
	}

	/**
	 * enable() on an already-active plugin should return true without side effects.
	 * It also updates stored state to keep it in sync.
	 */
	public function test_enable_returns_true_when_plugin_already_active(): void {
		$plugin_dir  = WP_PLUGIN_DIR . '/test-feature';
		$plugin_path = $plugin_dir . '/test-feature.php';

		if ( ! is_dir( $plugin_dir ) ) {
			mkdir( $plugin_dir, 0755, true );
		}
		file_put_contents( $plugin_path, "<?php\n/**\n * Plugin Name: Test Feature\n * Author: StellarWP\n */\n" );

		try {
			$this->mock_activate_plugin( self::PLUGIN_FILE );

			$result = $this->strategy->enable( $this->feature );

			$this->assertTrue( $result );
			$this->assertSame( '1', get_option( self::OPTION_KEY ) );
		} finally {
			deactivate_plugins( self::PLUGIN_FILE );
			if ( file_exists( $plugin_path ) ) {
				unlink( $plugin_path );
			}
			if ( is_dir( $plugin_dir ) ) {
				rmdir( $plugin_dir );
			}
		}
	}

	/**
	 * enable() returns a plugins_api_failed error when plugins_api()
	 * returns a WP_Error for the feature slug.
	 */
	public function test_enable_returns_plugins_api_failed_error(): void {
		$filter = static function ( $result, $action, $args ) {
			if ( 'plugin_information' === $action && $args->slug === 'test-feature' ) {
				return new WP_Error( 'plugins_api_failed', 'No info available.' );
			}
			return $result;
		};
		add_filter( 'plugins_api', $filter, 10, 3 );

		try {
			$result = $this->strategy->enable( $this->feature );

			$this->assertWPError( $result );
			$this->assertSame( 'plugins_api_failed', $result->get_error_code() );
		} finally {
			remove_filter( 'plugins_api', $filter, 10 );
		}
	}

	/**
	 * enable() returns a download_link_empty error when plugins_api()
	 * returns an object without a download_link property.
	 *
	 * This simulates the case where the plugins_api filter for features
	 * is registered but the feature has no download URL.
	 */
	public function test_enable_returns_download_link_empty_error(): void {
		$filter = static function ( $result, $action, $args ) {
			if ( 'plugin_information' === $action && $args->slug === 'test-feature' ) {
				return (object) [ 'slug' => 'test-feature' ];
			}
			return $result;
		};
		add_filter( 'plugins_api', $filter, 10, 3 );

		try {
			$result = $this->strategy->enable( $this->feature );

			$this->assertWPError( $result );
			$this->assertSame( 'download_link_empty', $result->get_error_code() );
		} finally {
			remove_filter( 'plugins_api', $filter, 10 );
		}
	}

	/**
	 * enable() returns an install_locked error when another install is already
	 * in progress for the same plugin slug.
	 */
	public function test_enable_returns_install_locked_error_when_concurrent_install_in_progress(): void {
		// Simulate an in-progress install by setting the transient lock.
		set_transient( self::LOCK_KEY, '1', 120 );

		$result = $this->strategy->enable( $this->feature );

		$this->assertWPError( $result );
		$this->assertSame( 'install_locked', $result->get_error_code() );
	}

	/**
	 * enable() skips installation when the plugin file already exists on disk
	 * and proceeds directly to activation.
	 *
	 * We simulate "installed but inactive" by placing a dummy plugin file on
	 * disk before calling enable().
	 */
	public function test_enable_skips_install_for_already_installed_but_inactive_plugin(): void {
		$plugin_dir  = WP_PLUGIN_DIR . '/test-feature';
		$plugin_path = $plugin_dir . '/test-feature.php';

		// Create a minimal valid plugin file on disk.
		if ( ! is_dir( $plugin_dir ) ) {
			mkdir( $plugin_dir, 0755, true );
		}
		file_put_contents( $plugin_path, "<?php\n/**\n * Plugin Name: Test Feature\n * Author: StellarWP\n */\n" );

		try {
			$result = $this->strategy->enable( $this->feature );

			// Should succeed — plugin was installed, just needed activation.
			$this->assertTrue( $result );
			$this->assertTrue( is_plugin_active( self::PLUGIN_FILE ) );
			$this->assertSame( '1', get_option( self::OPTION_KEY ) );

			// Verify no lock was left behind (it should have been released or
			// never acquired since the plugin was already on disk).
			$this->assertFalse( get_transient( self::LOCK_KEY ) );
		} finally {
			// Clean up the dummy plugin.
			deactivate_plugins( self::PLUGIN_FILE );
			if ( file_exists( $plugin_path ) ) {
				unlink( $plugin_path );
			}
			if ( is_dir( $plugin_dir ) ) {
				rmdir( $plugin_dir );
			}
		}
	}

	/**
	 * enable() releases the transient lock even when installation fails.
	 *
	 * We simulate a plugins_api() response with a fake download_link that
	 * Plugin_Upgrader::install() cannot fetch. The lock should be released
	 * in the finally block regardless.
	 */
	public function test_enable_releases_lock_on_install_failure(): void {
		// Make plugins_api() return a download_link so the strategy reaches
		// Plugin_Upgrader::install(), which will fail on the fake URL.
		$filter = static function ( $result, $action, $args ) {
			if ( 'plugin_information' === $action && $args->slug === 'test-feature' ) {
				return (object) [
					'slug'          => 'test-feature',
					'download_link' => 'https://example.com/test-feature.zip',
				];
			}
			return $result;
		};
		add_filter( 'plugins_api', $filter, 10, 3 );

		try {
			// Track output buffer level because Plugin_Upgrader's WP_Ajax_Upgrader_Skin
			// opens buffers that may not get closed on failure. PHPUnit flags the
			// mismatch as "risky", so we clean up any leftover buffers.
			$ob_level = ob_get_level();
			$result   = $this->strategy->enable( $this->feature );

			while ( ob_get_level() > $ob_level ) {
				ob_end_clean();
			}

			// Should fail (install_failed or similar).
			$this->assertWPError( $result );

			// Lock should be released.
			$this->assertFalse( get_transient( self::LOCK_KEY ) );
		} finally {
			remove_filter( 'plugins_api', $filter, 10 );
		}
	}

	// -------------------------------------------------------------------------
	// disable() tests
	// -------------------------------------------------------------------------

	/**
	 * disable() must reject non-Zip instances with a type mismatch error.
	 */
	public function test_disable_returns_type_mismatch_error_for_non_zip_feature(): void {
		$non_zip = $this->create_non_zip_feature();

		$result = $this->strategy->disable( $non_zip );

		$this->assertWPError( $result );
		$this->assertSame( 'feature_type_mismatch', $result->get_error_code() );
	}

	/**
	 * disable() deactivates an active plugin and updates stored state.
	 */
	public function test_disable_deactivates_active_plugin(): void {
		$this->mock_activate_plugin( self::PLUGIN_FILE );
		update_option( self::OPTION_KEY, '1', true );

		$result = $this->strategy->disable( $this->feature );

		$this->assertTrue( $result );
		$this->assertFalse( is_plugin_active( self::PLUGIN_FILE ) );
		$this->assertSame( '0', get_option( self::OPTION_KEY ) );
	}

	/**
	 * disable() on an already-inactive plugin should return true (idempotent)
	 * and update stored state to false.
	 */
	public function test_disable_returns_true_when_plugin_already_inactive(): void {
		// Plugin is not active. Stored state says active (stale).
		update_option( self::OPTION_KEY, '1', true );

		$result = $this->strategy->disable( $this->feature );

		$this->assertTrue( $result );
		$this->assertSame( '0', get_option( self::OPTION_KEY ) );
	}

	// -------------------------------------------------------------------------
	// is_active() tests
	// -------------------------------------------------------------------------

	/**
	 * is_active() returns false for non-Zip instances.
	 */
	public function test_is_active_returns_false_for_non_zip_feature(): void {
		$non_zip = $this->create_non_zip_feature();

		$this->assertFalse( $this->strategy->is_active( $non_zip ) );
	}

	/**
	 * is_active() returns true when the plugin is active in WordPress.
	 */
	public function test_is_active_returns_true_when_plugin_is_active(): void {
		$this->mock_activate_plugin( self::PLUGIN_FILE );

		$this->assertTrue( $this->strategy->is_active( $this->feature ) );
	}

	/**
	 * is_active() returns false when the plugin is inactive in WordPress.
	 */
	public function test_is_active_returns_false_when_plugin_is_inactive(): void {
		$this->assertFalse( $this->strategy->is_active( $this->feature ) );
	}

	/**
	 * is_active() self-heals a stale stored state of true when the plugin is
	 * actually inactive (e.g. deactivated via the Plugins admin page).
	 */
	public function test_is_active_self_heals_stale_true_to_false(): void {
		// Stored state says active, but plugin is not actually active.
		update_option( self::OPTION_KEY, '1', true );

		$result = $this->strategy->is_active( $this->feature );

		// Live state wins — plugin is inactive.
		$this->assertFalse( $result );
		// Stored state should be corrected.
		$this->assertSame( '0', get_option( self::OPTION_KEY ) );
	}

	/**
	 * is_active() self-heals a stale stored state of false when the plugin is
	 * actually active (e.g. activated via the Plugins admin page).
	 */
	public function test_is_active_self_heals_stale_false_to_true(): void {
		// Stored state says inactive, but plugin is actually active.
		update_option( self::OPTION_KEY, '0', true );
		$this->mock_activate_plugin( self::PLUGIN_FILE );

		$result = $this->strategy->is_active( $this->feature );

		// Live state wins — plugin is active.
		$this->assertTrue( $result );
		// Stored state should be corrected.
		$this->assertSame( '1', get_option( self::OPTION_KEY ) );
	}

	/**
	 * is_active() writes the correct stored state when no option exists yet
	 * (first time checking a feature).
	 */
	public function test_is_active_initializes_stored_state_when_missing(): void {
		// No option set. Plugin is inactive.
		$this->assertFalse( get_option( self::OPTION_KEY, false ) );

		$this->strategy->is_active( $this->feature );

		// Should write '0' for the inactive state.
		$this->assertSame( '0', get_option( self::OPTION_KEY ) );
	}

	// -------------------------------------------------------------------------
	// Sync hook tests
	// -------------------------------------------------------------------------

	/**
	 * on_plugin_activated updates stored state to true for a known feature.
	 */
	public function test_on_plugin_activated_updates_state_for_known_feature(): void {
		$strategy = new Zip_Strategy( function ( string $plugin_file ): ?Zip {
			if ( $plugin_file === self::PLUGIN_FILE ) {
				return $this->make_zip_feature();
			}
			return null;
		} );

		$strategy->on_plugin_activated( self::PLUGIN_FILE, false );

		$this->assertSame( '1', get_option( self::OPTION_KEY ) );
	}

	/**
	 * on_plugin_activated ignores unknown plugins (resolver returns null).
	 */
	public function test_on_plugin_activated_ignores_unknown_plugin(): void {
		$strategy = new Zip_Strategy( function ( string $plugin_file ): ?Zip {
			return null;
		} );

		$strategy->on_plugin_activated( 'unknown/unknown.php', false );

		// No option should be written for unknown features.
		$this->assertFalse( get_option( self::OPTION_KEY, false ) );
	}

	/**
	 * on_plugin_activated is a no-op when no feature_resolver is configured.
	 */
	public function test_on_plugin_activated_noops_without_resolver(): void {
		$this->strategy->on_plugin_activated( self::PLUGIN_FILE, false );

		// No option should be written when there's no resolver.
		$this->assertFalse( get_option( self::OPTION_KEY, false ) );
	}

	/**
	 * on_plugin_deactivated updates stored state to false for a known feature.
	 */
	public function test_on_plugin_deactivated_updates_state_for_known_feature(): void {
		// Start with active stored state.
		update_option( self::OPTION_KEY, '1', true );

		$strategy = new Zip_Strategy( function ( string $plugin_file ): ?Zip {
			if ( $plugin_file === self::PLUGIN_FILE ) {
				return $this->make_zip_feature();
			}
			return null;
		} );

		$strategy->on_plugin_deactivated( self::PLUGIN_FILE, false );

		$this->assertSame( '0', get_option( self::OPTION_KEY ) );
	}

	/**
	 * on_plugin_deactivated ignores unknown plugins (resolver returns null).
	 */
	public function test_on_plugin_deactivated_ignores_unknown_plugin(): void {
		$strategy = new Zip_Strategy( function ( string $plugin_file ): ?Zip {
			return null;
		} );

		update_option( self::OPTION_KEY, '1', true );

		$strategy->on_plugin_deactivated( 'unknown/unknown.php', false );

		// The known feature's state should not be affected.
		$this->assertSame( '1', get_option( self::OPTION_KEY ) );
	}

	// -------------------------------------------------------------------------
	// Ownership verification tests
	// -------------------------------------------------------------------------

	/**
	 * enable() returns a plugin_ownership_mismatch error when an installed-but-
	 * inactive plugin on disk has a different Author header than expected.
	 */
	public function test_enable_returns_ownership_mismatch_for_installed_plugin_with_wrong_author(): void {
		$plugin_dir  = WP_PLUGIN_DIR . '/test-feature';
		$plugin_path = $plugin_dir . '/test-feature.php';

		if ( ! is_dir( $plugin_dir ) ) {
			mkdir( $plugin_dir, 0755, true );
		}
		file_put_contents( $plugin_path, "<?php\n/**\n * Plugin Name: Test Feature\n * Author: Foreign Developer\n */\n" );

		try {
			$feature = $this->make_zip_feature( 'test-feature', self::PLUGIN_FILE, [ 'StellarWP' ] );
			$result  = $this->strategy->enable( $feature );

			$this->assertWPError( $result );
			$this->assertSame( 'plugin_ownership_mismatch', $result->get_error_code() );
			$this->assertStringContainsString( 'Foreign Developer', $result->get_error_message() );
		} finally {
			deactivate_plugins( self::PLUGIN_FILE );
			if ( file_exists( $plugin_path ) ) {
				unlink( $plugin_path );
			}
			if ( is_dir( $plugin_dir ) ) {
				rmdir( $plugin_dir );
			}
		}
	}

	/**
	 * enable() returns a plugin_ownership_mismatch error when an already-active
	 * plugin has a different Author header. Stored state should NOT be updated.
	 */
	public function test_enable_returns_ownership_mismatch_for_active_plugin_with_wrong_author(): void {
		$plugin_dir  = WP_PLUGIN_DIR . '/test-feature';
		$plugin_path = $plugin_dir . '/test-feature.php';

		if ( ! is_dir( $plugin_dir ) ) {
			mkdir( $plugin_dir, 0755, true );
		}
		file_put_contents( $plugin_path, "<?php\n/**\n * Plugin Name: Test Feature\n * Author: Foreign Developer\n */\n" );

		try {
			$this->mock_activate_plugin( self::PLUGIN_FILE );

			$feature = $this->make_zip_feature( 'test-feature', self::PLUGIN_FILE, [ 'StellarWP' ] );
			$result  = $this->strategy->enable( $feature );

			$this->assertWPError( $result );
			$this->assertSame( 'plugin_ownership_mismatch', $result->get_error_code() );
			// Stored state should NOT have been updated.
			$this->assertFalse( get_option( self::OPTION_KEY, false ) );
		} finally {
			deactivate_plugins( self::PLUGIN_FILE );
			if ( file_exists( $plugin_path ) ) {
				unlink( $plugin_path );
			}
			if ( is_dir( $plugin_dir ) ) {
				rmdir( $plugin_dir );
			}
		}
	}

	/**
	 * enable() succeeds when the installed plugin's Author header matches
	 * one of the expected authors on the feature.
	 */
	public function test_enable_succeeds_when_installed_plugin_has_matching_author(): void {
		$plugin_dir  = WP_PLUGIN_DIR . '/test-feature';
		$plugin_path = $plugin_dir . '/test-feature.php';

		if ( ! is_dir( $plugin_dir ) ) {
			mkdir( $plugin_dir, 0755, true );
		}
		file_put_contents( $plugin_path, "<?php\n/**\n * Plugin Name: Test Feature\n * Author: StellarWP\n */\n" );

		try {
			$feature = $this->make_zip_feature( 'test-feature', self::PLUGIN_FILE, [ 'StellarWP' ] );
			$result  = $this->strategy->enable( $feature );

			$this->assertTrue( $result );
			$this->assertTrue( is_plugin_active( self::PLUGIN_FILE ) );
			$this->assertSame( '1', get_option( self::OPTION_KEY ) );
		} finally {
			deactivate_plugins( self::PLUGIN_FILE );
			if ( file_exists( $plugin_path ) ) {
				unlink( $plugin_path );
			}
			if ( is_dir( $plugin_dir ) ) {
				rmdir( $plugin_dir );
			}
		}
	}

	/**
	 * enable() succeeds when the installed plugin's Author header matches
	 * the second entry in the authors array (e.g. a rebrand).
	 */
	public function test_enable_succeeds_when_author_matches_alternate_entry(): void {
		$plugin_dir  = WP_PLUGIN_DIR . '/test-feature';
		$plugin_path = $plugin_dir . '/test-feature.php';

		if ( ! is_dir( $plugin_dir ) ) {
			mkdir( $plugin_dir, 0755, true );
		}
		file_put_contents( $plugin_path, "<?php\n/**\n * Plugin Name: Test Feature\n * Author: The Events Calendar\n */\n" );

		try {
			$feature = $this->make_zip_feature( 'test-feature', self::PLUGIN_FILE, [ 'StellarWP', 'The Events Calendar' ] );
			$result  = $this->strategy->enable( $feature );

			$this->assertTrue( $result );
			$this->assertTrue( is_plugin_active( self::PLUGIN_FILE ) );
		} finally {
			deactivate_plugins( self::PLUGIN_FILE );
			if ( file_exists( $plugin_path ) ) {
				unlink( $plugin_path );
			}
			if ( is_dir( $plugin_dir ) ) {
				rmdir( $plugin_dir );
			}
		}
	}

	/**
	 * enable() skips the ownership check when the feature has an empty
	 * authors array, even if the installed plugin belongs to a different
	 * developer.
	 */
	public function test_enable_skips_ownership_check_when_authors_is_empty(): void {
		$plugin_dir  = WP_PLUGIN_DIR . '/test-feature';
		$plugin_path = $plugin_dir . '/test-feature.php';

		if ( ! is_dir( $plugin_dir ) ) {
			mkdir( $plugin_dir, 0755, true );
		}
		file_put_contents( $plugin_path, "<?php\n/**\n * Plugin Name: Test Feature\n * Author: Foreign Developer\n */\n" );

		try {
			// Empty authors array — ownership check should be skipped.
			$feature = $this->make_zip_feature( 'test-feature', self::PLUGIN_FILE, [] );
			$result  = $this->strategy->enable( $feature );

			$this->assertTrue( $result );
			$this->assertTrue( is_plugin_active( self::PLUGIN_FILE ) );
		} finally {
			deactivate_plugins( self::PLUGIN_FILE );
			if ( file_exists( $plugin_path ) ) {
				unlink( $plugin_path );
			}
			if ( is_dir( $plugin_dir ) ) {
				rmdir( $plugin_dir );
			}
		}
	}

	/**
	 * enable() normalizes author comparison: case differences and whitespace
	 * should still match.
	 *
	 * @dataProvider author_normalization_provider
	 *
	 * @param string $expected_author Author value on the Zip feature.
	 * @param string $actual_author   Author header in the plugin file.
	 */
	public function test_enable_normalizes_author_comparison(
		string $expected_author,
		string $actual_author
	): void {
		$plugin_dir  = WP_PLUGIN_DIR . '/test-feature';
		$plugin_path = $plugin_dir . '/test-feature.php';

		if ( ! is_dir( $plugin_dir ) ) {
			mkdir( $plugin_dir, 0755, true );
		}
		file_put_contents( $plugin_path, "<?php\n/**\n * Plugin Name: Test Feature\n * Author: {$actual_author}\n */\n" );

		try {
			$feature = $this->make_zip_feature( 'test-feature', self::PLUGIN_FILE, [ $expected_author ] );
			$result  = $this->strategy->enable( $feature );

			$this->assertTrue( $result, sprintf(
				'Expected author "%s" should match actual author "%s".',
				$expected_author,
				$actual_author
			) );
		} finally {
			deactivate_plugins( self::PLUGIN_FILE );
			if ( file_exists( $plugin_path ) ) {
				unlink( $plugin_path );
			}
			if ( is_dir( $plugin_dir ) ) {
				rmdir( $plugin_dir );
			}
		}
	}

	/**
	 * Data provider for author normalization tests.
	 *
	 * @return array<string, array{string, string}>
	 */
	public function author_normalization_provider(): array {
		return [
			'exact match'           => [ 'StellarWP', 'StellarWP' ],
			'case difference'       => [ 'stellarwp', 'StellarWP' ],
			'uppercase expected'    => [ 'STELLARWP', 'StellarWP' ],
			'leading whitespace'    => [ ' StellarWP', 'StellarWP' ],
			'trailing whitespace'   => [ 'StellarWP ', 'StellarWP' ],
			'both have whitespace'  => [ ' StellarWP ', ' StellarWP ' ],
		];
	}

	// -------------------------------------------------------------------------
	// Pre-flight check tests
	// -------------------------------------------------------------------------

	/**
	 * enable() rejects a plugin with unmet requirements in pre-flight checks
	 * before attempting activation.
	 */
	public function test_enable_preflight_rejects_invalid_plugin(): void {
		$plugin_dir  = WP_PLUGIN_DIR . '/bad-plugin';
		$plugin_path = $plugin_dir . '/bad-plugin.php';

		if ( ! is_dir( $plugin_dir ) ) {
			mkdir( $plugin_dir, 0755, true );
		}
		// Write a plugin requiring PHP 99.0 — validate_plugin_requirements() will reject it.
		// Include Author header to pass ownership verification so pre-flight runs.
		file_put_contents( $plugin_path, "<?php\n/**\n * Plugin Name: Bad Plugin\n * Author: StellarWP\n * Requires PHP: 99.0\n */\n" );

		try {
			$feature = $this->make_zip_feature(
				'bad-plugin',
				'bad-plugin/bad-plugin.php'
			);

			$result = $this->strategy->enable( $feature );

			$this->assertWPError( $result );
			$this->assertSame( 'preflight_requirements_not_met', $result->get_error_code() );
		} finally {
			if ( file_exists( $plugin_path ) ) {
				unlink( $plugin_path );
			}
			if ( is_dir( $plugin_dir ) ) {
				rmdir( $plugin_dir );
			}
		}
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Create a standard Zip feature for testing.
	 *
	 * @param string   $slug         Feature slug.
	 * @param string   $plugin_file  Plugin file path.
	 * @param string[] $authors      Expected plugin authors.
	 *
	 * @return Zip
	 */
	private function make_zip_feature(
		string $slug = 'test-feature',
		string $plugin_file = self::PLUGIN_FILE,
		array $authors = [ 'StellarWP' ]
	): Zip {
		return new Zip( [
			'slug'         => $slug,
			'group'        => 'Test',
			'tier'         => 'Tier 1',
			'name'         => 'Test Feature',
			'description'  => 'A test feature for unit tests.',
			'plugin_file'  => $plugin_file,
			'is_available' => true,
			'authors'      => $authors,
		] );
	}

	/**
	 * Create a non-Zip Feature subclass for type-guard testing.
	 *
	 * Uses an anonymous class to avoid creating a whole new file for a test-
	 * only concrete subclass.
	 *
	 * @return Feature
	 */
	private function create_non_zip_feature(): Feature {
		return new class ( [
			'slug'         => 'non-zip',
			'group'        => 'Test',
			'tier'         => 'Tier 1',
			'name'         => 'Non-Zip Feature',
			'description'  => 'Not a zip.',
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

}
