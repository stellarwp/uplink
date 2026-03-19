<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Features\Strategy;

use StellarWP\Uplink\Features\Error_Code;
use StellarWP\Uplink\Features\Strategy\Plugin_Strategy;
use StellarWP\Uplink\Features\Types\Plugin;
use StellarWP\Uplink\Tests\UplinkTestCase;
use WP_Error;
use WP_Upgrader;

/**
 * Tests for the Plugin_Strategy feature-gating strategy.
 *
 * These tests exercise the strategy's logic against real WordPress state
 * (active_plugins option, WP_Upgrader locks) via the WPLoader module.
 * Plugin installation is not tested here — it requires actual filesystem and
 * HTTP operations better suited to integration tests with a real ZIP URL.
 *
 * Plugin features derive their active state from live WordPress plugin state.
 * No DB option is stored.
 *
 * @see Plugin_Strategy
 */
final class PluginStrategyTest extends UplinkTestCase {

	/**
	 * Test plugin file path used across tests.
	 *
	 * @var string
	 */
	private const PLUGIN_FILE = 'test-feature/test-feature.php';

	/**
	 * @var Plugin_Strategy
	 */
	private $strategy;

	/**
	 * @var Plugin
	 */
	private $feature;

	protected function setUp(): void {
		parent::setUp();

		// Load plugin.php so is_plugin_active() etc. are available.
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		if ( ! class_exists( 'WP_Upgrader' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}

		$this->feature  = $this->make_plugin_feature();
		$this->strategy = new Plugin_Strategy( $this->feature );
	}

	protected function tearDown(): void {
		// Clean up locks.
		WP_Upgrader::release_lock( 'stellarwp_uplink_install_lock' );

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
	 * enable() on an already-active plugin should return true without side effects.
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

			$result = $this->strategy->enable();

			$this->assertTrue( $result );
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
			$result = $this->strategy->enable();

			$this->assertWPError( $result );
			$this->assertSame( Error_Code::PLUGINS_API_FAILED, $result->get_error_code() );
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
			$result = $this->strategy->enable();

			$this->assertWPError( $result );
			$this->assertSame( Error_Code::DOWNLOAD_LINK_MISSING, $result->get_error_code() );
		} finally {
			remove_filter( 'plugins_api', $filter, 10 );
		}
	}

	/**
	 * enable() returns an install_locked error when another install is already
	 * in progress (global lock).
	 */
	public function test_enable_returns_install_locked_error_when_concurrent_install_in_progress(): void {
		// Simulate an in-progress install by acquiring the WP_Upgrader lock.
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
		$plugin_dir  = WP_PLUGIN_DIR . '/test-feature';
		$plugin_path = $plugin_dir . '/test-feature.php';

		if ( ! is_dir( $plugin_dir ) ) {
			mkdir( $plugin_dir, 0755, true );
		}
		file_put_contents( $plugin_path, "<?php\n/**\n * Plugin Name: Test Feature\n * Author: StellarWP\n */\n" );

		// Simulate a stale lock from 5 minutes ago (TTL is 2 minutes).
		update_option( 'stellarwp_uplink_install_lock.lock', time() - 300, false );

		try {
			$result = $this->strategy->enable();

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
			$result = $this->strategy->enable();

			// Should succeed — plugin was installed, just needed activation.
			$this->assertTrue( $result );
			$this->assertTrue( is_plugin_active( self::PLUGIN_FILE ) );

			// Verify no lock was left behind (it should have been released or
			// never acquired since the plugin was already on disk).
			$this->assertFalse( get_option( 'stellarwp_uplink_install_lock.lock' ) );
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
	 * enable() releases the install lock even when installation fails.
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
			$result   = $this->strategy->enable();

			while ( ob_get_level() > $ob_level ) {
				ob_end_clean();
			}

			// Should fail (install_failed or similar).
			$this->assertWPError( $result );

			// Lock should be released.
			$this->assertFalse( get_option( 'stellarwp_uplink_install_lock.lock' ) );
		} finally {
			remove_filter( 'plugins_api', $filter, 10 );
		}
	}

	// -------------------------------------------------------------------------
	// disable() tests
	// -------------------------------------------------------------------------

	/**
	 * disable() deactivates an active plugin.
	 */
	public function test_disable_deactivates_active_plugin(): void {
		$this->mock_activate_plugin( self::PLUGIN_FILE );

		$result = $this->strategy->disable();

		$this->assertTrue( $result );
		$this->assertFalse( is_plugin_active( self::PLUGIN_FILE ) );
	}

	/**
	 * disable() on an already-inactive plugin should return true (idempotent).
	 */
	public function test_disable_returns_true_when_plugin_already_inactive(): void {
		$result = $this->strategy->disable();

		$this->assertTrue( $result );
	}

	// -------------------------------------------------------------------------
	// is_active() tests
	// -------------------------------------------------------------------------

	/**
	 * is_active() returns true when the plugin is active in WordPress.
	 */
	public function test_is_active_returns_true_when_plugin_is_active(): void {
		$this->mock_activate_plugin( self::PLUGIN_FILE );

		$this->assertTrue( $this->strategy->is_active() );
	}

	/**
	 * is_active() returns false when the plugin is inactive in WordPress.
	 */
	public function test_is_active_returns_false_when_plugin_is_inactive(): void {
		$this->assertFalse( $this->strategy->is_active() );
	}

	/**
	 * is_active() reflects live plugin state regardless of any stale DB option.
	 */
	public function test_is_active_ignores_stale_stored_state(): void {
		// Even if a stale option exists, is_active() only checks live state.
		update_option( 'stellarwp_uplink_feature_test-feature_active', '1', true );

		$this->assertFalse( $this->strategy->is_active() );
	}

	/**
	 * is_active() does not write any DB option.
	 */
	public function test_is_active_does_not_write_stored_state(): void {
		$this->strategy->is_active();

		$this->assertFalse( get_option( 'stellarwp_uplink_feature_test-feature_active', false ) );
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
			$feature  = $this->make_plugin_feature( 'test-feature', self::PLUGIN_FILE, [ 'StellarWP' ] );
			$strategy = new Plugin_Strategy( $feature );
			$result   = $strategy->enable();

			$this->assertWPError( $result );
			$this->assertSame( Error_Code::PLUGIN_OWNERSHIP_MISMATCH, $result->get_error_code() );
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

			$feature  = $this->make_plugin_feature( 'test-feature', self::PLUGIN_FILE, [ 'StellarWP' ] );
			$strategy = new Plugin_Strategy( $feature );
			$result   = $strategy->enable();

			$this->assertWPError( $result );
			$this->assertSame( Error_Code::PLUGIN_OWNERSHIP_MISMATCH, $result->get_error_code() );
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
	 * enable() returns a plugin_ownership_mismatch error when the folder exists
	 * with a different developer's plugin under a different file name.
	 *
	 * Example: folder "test-feature/" exists with "other-plugin.php" by
	 * "Foreign Developer", but the feature expects "test-feature/test-feature.php".
	 */
	public function test_enable_returns_ownership_mismatch_for_folder_occupied_by_different_file(): void {
		$plugin_dir   = WP_PLUGIN_DIR . '/test-feature';
		$foreign_file = $plugin_dir . '/other-plugin.php';

		if ( ! is_dir( $plugin_dir ) ) {
			mkdir( $plugin_dir, 0755, true );
		}
		// A different plugin by a different developer in the same folder.
		file_put_contents( $foreign_file, "<?php\n/**\n * Plugin Name: Other Plugin\n * Author: Foreign Developer\n */\n" );

		try {
			// Our feature expects test-feature/test-feature.php, which doesn't exist.
			$feature  = $this->make_plugin_feature( 'test-feature', self::PLUGIN_FILE, [ 'StellarWP' ] );
			$strategy = new Plugin_Strategy( $feature );
			$result   = $strategy->enable();

			$this->assertWPError( $result );
			$this->assertSame( Error_Code::PLUGIN_OWNERSHIP_MISMATCH, $result->get_error_code() );
			$this->assertStringContainsString( 'Foreign Developer', $result->get_error_message() );
			$this->assertStringContainsString( 'Other Plugin', $result->get_error_message() );
		} finally {
			if ( file_exists( $foreign_file ) ) {
				unlink( $foreign_file );
			}
			if ( is_dir( $plugin_dir ) ) {
				rmdir( $plugin_dir );
			}
		}
	}

	/**
	 * enable() succeeds when the folder exists with a different file name but
	 * the plugin belongs to an expected author — no ownership conflict.
	 */
	public function test_enable_no_conflict_when_folder_has_same_author_different_file(): void {
		$plugin_dir  = WP_PLUGIN_DIR . '/test-feature';
		$other_file  = $plugin_dir . '/other-plugin.php';
		$plugin_path = $plugin_dir . '/test-feature.php';

		if ( ! is_dir( $plugin_dir ) ) {
			mkdir( $plugin_dir, 0755, true );
		}
		// Another plugin by the SAME developer in the folder.
		file_put_contents( $other_file, "<?php\n/**\n * Plugin Name: Other Plugin\n * Author: StellarWP\n */\n" );

		// Make plugins_api() return a download link so it reaches the install step,
		// but the install will fail. The key assertion is that the ownership check passes.
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
			$ob_level = ob_get_level();
			$feature  = $this->make_plugin_feature( 'test-feature', self::PLUGIN_FILE, [ 'StellarWP' ] );
			$strategy = new Plugin_Strategy( $feature );
			$result   = $strategy->enable();

			while ( ob_get_level() > $ob_level ) {
				ob_end_clean();
			}

			// The ownership check should pass (same author). The result will be
			// an install error because the download URL is fake — but NOT an
			// ownership mismatch.
			if ( is_wp_error( $result ) ) {
				$this->assertNotSame( Error_Code::PLUGIN_OWNERSHIP_MISMATCH, $result->get_error_code() );
			}
		} finally {
			remove_filter( 'plugins_api', $filter, 10 );
			if ( file_exists( $other_file ) ) {
				unlink( $other_file );
			}
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
			$feature  = $this->make_plugin_feature( 'test-feature', self::PLUGIN_FILE, [ 'StellarWP' ] );
			$strategy = new Plugin_Strategy( $feature );
			$result   = $strategy->enable();

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
			$feature  = $this->make_plugin_feature( 'test-feature', self::PLUGIN_FILE, [ 'StellarWP', 'The Events Calendar' ] );
			$strategy = new Plugin_Strategy( $feature );
			$result   = $strategy->enable();

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
			$feature  = $this->make_plugin_feature( 'test-feature', self::PLUGIN_FILE, [] );
			$strategy = new Plugin_Strategy( $feature );
			$result   = $strategy->enable();

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
	 * @param string $expected_author Author value on the Plugin feature.
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
			$feature  = $this->make_plugin_feature( 'test-feature', self::PLUGIN_FILE, [ $expected_author ] );
			$strategy = new Plugin_Strategy( $feature );
			$result   = $strategy->enable();

			$this->assertTrue(
				$result,
				sprintf(
					'Expected author "%s" should match actual author "%s".',
					$expected_author,
					$actual_author
				)
			);
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
			'exact match'          => [ 'StellarWP', 'StellarWP' ],
			'case difference'      => [ 'stellarwp', 'StellarWP' ],
			'uppercase expected'   => [ 'STELLARWP', 'StellarWP' ],
			'leading whitespace'   => [ ' StellarWP', 'StellarWP' ],
			'trailing whitespace'  => [ 'StellarWP ', 'StellarWP' ],
			'both have whitespace' => [ ' StellarWP ', ' StellarWP ' ],
		];
	}

	// -------------------------------------------------------------------------
	// Requirements tests
	// -------------------------------------------------------------------------

	/**
	 * enable() returns REQUIREMENTS_NOT_MET when an already-installed plugin
	 * requires an impossibly high PHP version (detected during activation via
	 * validate_plugin_requirements).
	 */
	public function test_enable_returns_requirements_not_met_for_high_php_requirement(): void {
		$plugin_slug = 'high-php-req-plugin-feature';
		$plugin_file = $plugin_slug . '/' . $plugin_slug . '.php';
		$plugin_dir  = WP_PLUGIN_DIR . '/' . $plugin_slug;
		$source_dir  = codecept_data_dir( 'Features/Plugins/' . $plugin_slug );

		if ( ! is_dir( $plugin_dir ) ) {
			mkdir( $plugin_dir, 0755, true );
		}
		copy( $source_dir . '/' . $plugin_slug . '.php', $plugin_dir . '/' . $plugin_slug . '.php' );

		try {
			$feature  = $this->make_plugin_feature( $plugin_slug, $plugin_file, [ 'StellarWP' ] );
			$strategy = new Plugin_Strategy( $feature );
			$result   = $strategy->enable();

			$this->assertWPError( $result );
			$this->assertSame( Error_Code::REQUIREMENTS_NOT_MET, $result->get_error_code() );
			$this->assertStringContainsString( 'PHP', $result->get_error_message() );
		} finally {
			deactivate_plugins( $plugin_file );
			if ( file_exists( $plugin_dir . '/' . $plugin_slug . '.php' ) ) {
				unlink( $plugin_dir . '/' . $plugin_slug . '.php' );
			}
			if ( is_dir( $plugin_dir ) ) {
				rmdir( $plugin_dir );
			}
		}
	}

	/**
	 * enable() returns REQUIREMENTS_NOT_MET when an already-installed plugin
	 * requires an impossibly high WordPress version (detected during activation
	 * via validate_plugin_requirements).
	 */
	public function test_enable_returns_requirements_not_met_for_high_wp_requirement(): void {
		$plugin_slug = 'high-wp-req-plugin-feature';
		$plugin_file = $plugin_slug . '/' . $plugin_slug . '.php';
		$plugin_dir  = WP_PLUGIN_DIR . '/' . $plugin_slug;
		$source_dir  = codecept_data_dir( 'Features/Plugins/' . $plugin_slug );

		if ( ! is_dir( $plugin_dir ) ) {
			mkdir( $plugin_dir, 0755, true );
		}
		copy( $source_dir . '/' . $plugin_slug . '.php', $plugin_dir . '/' . $plugin_slug . '.php' );

		try {
			$feature  = $this->make_plugin_feature( $plugin_slug, $plugin_file, [ 'StellarWP' ] );
			$strategy = new Plugin_Strategy( $feature );
			$result   = $strategy->enable();

			$this->assertWPError( $result );
			$this->assertSame( Error_Code::REQUIREMENTS_NOT_MET, $result->get_error_code() );
			$this->assertStringContainsString( 'WordPress', $result->get_error_message() );
		} finally {
			deactivate_plugins( $plugin_file );
			if ( file_exists( $plugin_dir . '/' . $plugin_slug . '.php' ) ) {
				unlink( $plugin_dir . '/' . $plugin_slug . '.php' );
			}
			if ( is_dir( $plugin_dir ) ) {
				rmdir( $plugin_dir );
			}
		}
	}

	// -------------------------------------------------------------------------
	// Activation fatal error tests
	// -------------------------------------------------------------------------

	/**
	 * enable() returns ACTIVATION_FATAL when a plugin throws on include.
	 *
	 * The "fatal-plugin-feature" fixture throws a RuntimeException at the
	 * top level (outside any hook), simulating a plugin that fatals on
	 * first load during activate_plugin().
	 *
	 * Also verifies the error message does NOT leak exception details
	 * (message, file path) to the user — only the feature name is shown.
	 */
	public function test_enable_returns_activation_fatal_when_plugin_throws_on_include(): void {
		$plugin_slug = 'fatal-plugin-feature';
		$plugin_file = $plugin_slug . '/' . $plugin_slug . '.php';
		$plugin_dir  = WP_PLUGIN_DIR . '/' . $plugin_slug;
		$source_dir  = codecept_data_dir( 'Features/Plugins/' . $plugin_slug );

		if ( ! is_dir( $plugin_dir ) ) {
			mkdir( $plugin_dir, 0755, true );
		}
		copy( $source_dir . '/' . $plugin_slug . '.php', $plugin_dir . '/' . $plugin_slug . '.php' );

		try {
			$feature  = $this->make_plugin_feature( $plugin_slug, $plugin_file, [ 'StellarWP' ] );
			$strategy = new Plugin_Strategy( $feature );
			$result   = $strategy->enable();

			$this->assertWPError( $result );
			$this->assertSame( Error_Code::ACTIVATION_FATAL, $result->get_error_code() );

			$message = $result->get_error_message();

			// The exception message from the fixture must not appear in the user-facing error.
			$this->assertStringNotContainsString( 'Intentional fatal error', $message );
			// Server file paths must not leak.
			$this->assertStringNotContainsString( '.php', $message );
		} finally {
			deactivate_plugins( $plugin_file );
			if ( file_exists( $plugin_dir . '/' . $plugin_slug . '.php' ) ) {
				unlink( $plugin_dir . '/' . $plugin_slug . '.php' );
			}
			if ( is_dir( $plugin_dir ) ) {
				rmdir( $plugin_dir );
			}
		}
	}

	/**
	 * enable() returns ACTIVATION_FATAL when a plugin's activation hook throws.
	 *
	 * The "activation-fatal-plugin-feature" fixture registers a
	 * register_activation_hook() callback that throws a RuntimeException.
	 */
	public function test_enable_returns_activation_fatal_when_activation_hook_throws(): void {
		$plugin_slug = 'activation-fatal-plugin-feature';
		$plugin_file = $plugin_slug . '/' . $plugin_slug . '.php';
		$plugin_dir  = WP_PLUGIN_DIR . '/' . $plugin_slug;
		$source_dir  = codecept_data_dir( 'Features/Plugins/' . $plugin_slug );

		if ( ! is_dir( $plugin_dir ) ) {
			mkdir( $plugin_dir, 0755, true );
		}
		copy( $source_dir . '/' . $plugin_slug . '.php', $plugin_dir . '/' . $plugin_slug . '.php' );

		try {
			$feature  = $this->make_plugin_feature( $plugin_slug, $plugin_file, [ 'StellarWP' ] );
			$strategy = new Plugin_Strategy( $feature );
			$result   = $strategy->enable();

			$this->assertWPError( $result );
			$this->assertSame( Error_Code::ACTIVATION_FATAL, $result->get_error_code() );
		} finally {
			deactivate_plugins( $plugin_file );
			if ( file_exists( $plugin_dir . '/' . $plugin_slug . '.php' ) ) {
				unlink( $plugin_dir . '/' . $plugin_slug . '.php' );
			}
			if ( is_dir( $plugin_dir ) ) {
				rmdir( $plugin_dir );
			}
		}
	}

	/**
	 * enable() returns ACTIVATION_FATAL when a plugin has a PHP syntax error
	 * (ParseError thrown during include).
	 */
	public function test_enable_returns_activation_fatal_for_syntax_error_plugin(): void {
		$plugin_slug = 'syntax-error-plugin-feature';
		$plugin_file = $plugin_slug . '/' . $plugin_slug . '.php';
		$plugin_dir  = WP_PLUGIN_DIR . '/' . $plugin_slug;
		$source_dir  = codecept_data_dir( 'Features/Plugins/' . $plugin_slug );

		if ( ! is_dir( $plugin_dir ) ) {
			mkdir( $plugin_dir, 0755, true );
		}
		copy( $source_dir . '/' . $plugin_slug . '.php', $plugin_dir . '/' . $plugin_slug . '.php' );

		try {
			$feature  = $this->make_plugin_feature( $plugin_slug, $plugin_file, [ 'StellarWP' ] );
			$strategy = new Plugin_Strategy( $feature );
			$result   = $strategy->enable();

			$this->assertWPError( $result );
			$this->assertSame( Error_Code::ACTIVATION_FATAL, $result->get_error_code() );
			// Must not leak the parse error details.
			$this->assertStringNotContainsString( 'syntax', $result->get_error_message() );
		} finally {
			deactivate_plugins( $plugin_file );
			if ( file_exists( $plugin_dir . '/' . $plugin_slug . '.php' ) ) {
				unlink( $plugin_dir . '/' . $plugin_slug . '.php' );
			}
			if ( is_dir( $plugin_dir ) ) {
				rmdir( $plugin_dir );
			}
		}
	}

	// -------------------------------------------------------------------------
	// Install fatal error tests
	// -------------------------------------------------------------------------

	/**
	 * enable() returns INSTALL_FAILED when Plugin_Upgrader::install() throws
	 * a fatal error (Throwable), and releases the install lock afterward.
	 *
	 * Simulates a catastrophic failure during the download/unpack phase by
	 * hooking into the HTTP layer to throw an exception.
	 */
	public function test_enable_returns_install_failed_when_upgrader_install_throws(): void {
		// Make plugins_api() return a valid download link.
		$api_filter = static function ( $result, $action, $args ) {
			if ( 'plugin_information' === $action && $args->slug === 'test-feature' ) {
				return (object) [
					'slug'          => 'test-feature',
					'download_link' => 'https://example.com/test-feature.zip',
				];
			}
			return $result;
		};
		add_filter( 'plugins_api', $api_filter, 10, 3 );

		// Force the HTTP request to throw a Throwable during download.
		$http_filter = static function ( $response, $parsed_args, $url ) {
			if ( strpos( $url, 'example.com/test-feature.zip' ) !== false ) {
				throw new \RuntimeException( 'Simulated fatal during plugin download.' );
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
			remove_filter( 'plugins_api', $api_filter, 10 );
			remove_filter( 'pre_http_request', $http_filter, 10 );
		}
	}

	// -------------------------------------------------------------------------
	// update() tests
	// -------------------------------------------------------------------------

	/**
	 * update() returns FEATURE_NOT_ACTIVE when the plugin is not installed.
	 */
	public function test_update_returns_not_active_when_plugin_not_installed(): void {
		$result = $this->strategy->update();

		$this->assertWPError( $result );
		$this->assertSame( Error_Code::FEATURE_NOT_ACTIVE, $result->get_error_code() );
	}

	/**
	 * update() returns NO_UPDATE_AVAILABLE when the plugin is active but
	 * the WordPress update transient has no update for this plugin.
	 */
	public function test_update_returns_no_update_available_when_transient_empty(): void {
		$plugin_dir  = WP_PLUGIN_DIR . '/test-feature';
		$plugin_path = $plugin_dir . '/test-feature.php';

		if ( ! is_dir( $plugin_dir ) ) {
			mkdir( $plugin_dir, 0755, true );
		}
		file_put_contents( $plugin_path, "<?php\n/**\n * Plugin Name: Test Feature\n * Author: StellarWP\n */\n" );

		try {
			$this->mock_activate_plugin( self::PLUGIN_FILE );

			$result = $this->strategy->update();

			$this->assertWPError( $result );
			$this->assertSame( Error_Code::NO_UPDATE_AVAILABLE, $result->get_error_code() );
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
	 * update() returns INSTALL_LOCKED when the global lock is held.
	 */
	public function test_update_returns_install_locked_when_lock_held(): void {
		$plugin_dir  = WP_PLUGIN_DIR . '/test-feature';
		$plugin_path = $plugin_dir . '/test-feature.php';

		if ( ! is_dir( $plugin_dir ) ) {
			mkdir( $plugin_dir, 0755, true );
		}
		file_put_contents( $plugin_path, "<?php\n/**\n * Plugin Name: Test Feature\n * Author: StellarWP\n * Version: 1.0.0\n */\n" );

		try {
			$this->mock_activate_plugin( self::PLUGIN_FILE );

			// Seed the update transient.
			set_site_transient(
				'update_plugins',
				(object) [
					'response' => [
						self::PLUGIN_FILE => (object) [
							'slug'        => 'test-feature',
							'new_version' => '2.0.0',
							'package'     => 'https://example.com/test-feature-2.0.0.zip',
						],
					],
				]
			);

			WP_Upgrader::create_lock( 'stellarwp_uplink_install_lock', 120 );

			$result = $this->strategy->update();

			$this->assertWPError( $result );
			$this->assertSame( Error_Code::INSTALL_LOCKED, $result->get_error_code() );
		} finally {
			deactivate_plugins( self::PLUGIN_FILE );
			delete_site_transient( 'update_plugins' );
			if ( file_exists( $plugin_path ) ) {
				unlink( $plugin_path );
			}
			if ( is_dir( $plugin_dir ) ) {
				rmdir( $plugin_dir );
			}
		}
	}

	/**
	 * update() returns PLUGIN_OWNERSHIP_MISMATCH when the plugin belongs to
	 * a different developer.
	 */
	public function test_update_returns_ownership_mismatch(): void {
		$plugin_dir  = WP_PLUGIN_DIR . '/test-feature';
		$plugin_path = $plugin_dir . '/test-feature.php';

		if ( ! is_dir( $plugin_dir ) ) {
			mkdir( $plugin_dir, 0755, true );
		}
		file_put_contents( $plugin_path, "<?php\n/**\n * Plugin Name: Test Feature\n * Author: Foreign Developer\n */\n" );

		try {
			$this->mock_activate_plugin( self::PLUGIN_FILE );

			$feature  = $this->make_plugin_feature( 'test-feature', self::PLUGIN_FILE, [ 'StellarWP' ] );
			$strategy = new Plugin_Strategy( $feature );
			$result   = $strategy->update();

			$this->assertWPError( $result );
			$this->assertSame( Error_Code::PLUGIN_OWNERSHIP_MISMATCH, $result->get_error_code() );
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

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Create a standard Plugin feature for testing.
	 *
	 * @param string   $slug         Feature slug.
	 * @param string   $plugin_file  Plugin file path.
	 * @param string[] $authors      Expected plugin authors.
	 *
	 * @return Plugin
	 */
	private function make_plugin_feature(
		string $slug = 'test-feature',
		string $plugin_file = self::PLUGIN_FILE,
		array $authors = [ 'StellarWP' ]
	): Plugin {
		return new Plugin(
			[
				'slug'         => $slug,
				'product'        => 'Test',
				'tier'         => 'Tier 1',
				'name'         => 'Test Feature',
				'description'  => 'A test feature for unit tests.',
				'plugin_file'  => $plugin_file,
				'is_available' => true,
				'authors'      => $authors,
			]
		);
	}
}
