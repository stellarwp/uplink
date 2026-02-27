<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Strategy;

use StellarWP\Uplink\Features\Types\Feature;
use StellarWP\Uplink\Features\Types\Zip;
use StellarWP\Uplink\Utils\Cast;
use WP_Error;
use Throwable;
use WP_Ajax_Upgrader_Skin;
use Plugin_Upgrader;

/**
 * Zip Strategy — installs, activates, and deactivates WordPress plugins as
 * "features" using ZIP file downloads.
 *
 * This strategy handles the full lifecycle:
 * - enable()  → install (if needed) + activate
 * - disable() → deactivate (never deletes files — safe and reversible)
 * - is_active() → live WP check with self-healing stored state
 *
 * Concurrency is protected by per-slug transient locks. Fatal errors during
 * activation are caught via try/catch Throwable.
 *
 * Stored state lives in wp_options as `stellarwp_uplink_feature_{slug}_active`
 * with autoload=true for fast reads. The live WordPress plugin state is always
 * the source of truth — stored state is a cache that self-heals on mismatch.
 *
 * Sync hook callbacks (on_plugin_activated / on_plugin_deactivated) are public
 * methods intended to be wired to WordPress hooks by the Provider layer. They
 * use the optional $feature_resolver callable to look up features from the
 * Collection. Until the Provider is built, these methods are inert.
 *
 * @since 3.0.0
 */
class Zip_Strategy extends Abstract_Strategy {

	/**
	 * Transient lock TTL in seconds.
	 *
	 * 120 seconds is generous enough to cover slow downloads on shared hosting,
	 * but short enough that a crashed install won't block retries for long.
	 *
	 * @since 3.0.0
	 */
	private const LOCK_TTL = 120;

	/**
	 * Transient key prefix for install locks.
	 *
	 * Full key: stellarwp_uplink_install_lock_{slug}
	 *
	 * @since 3.0.0
	 */
	private const LOCK_PREFIX = 'stellarwp_uplink_install_lock_';

	/**
	 * Optional callable that resolves a plugin_file string to a Zip feature.
	 *
	 * Signature: fn(string $plugin_file): ?Zip
	 *
	 * The Provider layer wires this to the Feature Collection. Until then,
	 * sync hook callbacks (on_plugin_activated / on_plugin_deactivated) will
	 * silently no-op because the resolver returns null.
	 *
	 * @since 3.0.0
	 *
	 * @var callable|null
	 */
	private $feature_resolver;

	/**
	 * Construct the Zip_Strategy.
	 *
	 * @since 3.0.0
	 *
	 * @param callable|null $feature_resolver Optional. Resolves a plugin_file
	 *                                        string to a Zip instance.
	 */
	public function __construct( ?callable $feature_resolver = null ) {
		$this->feature_resolver = $feature_resolver;
	}

	/**
	 * Enable a Zip feature: install (if needed) and activate the plugin.
	 *
	 * Idempotent: returns true if the plugin is already active. Uses a
	 * transient lock to prevent concurrent installs of the same plugin.
	 *
	 * @since 3.0.0
	 *
	 * @param Feature $feature Must be a Zip instance.
	 *
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public function enable( Feature $feature ) {
		// Type-guard: Zip_Strategy only handles Zip instances.
		if ( ! $feature instanceof Zip ) {
			return new WP_Error(
				'feature_type_mismatch',
				'Zip_Strategy can only enable Zip instances.'
			);
		}

		// Ensure WordPress admin functions are available. These may not be
		// loaded when called from REST API or AJAX contexts.
		$this->load_wp_admin_includes();

		$plugin_file = $feature->get_plugin_file();

		// Idempotent: if the plugin is already active, verify ownership and bail.
		if ( $this->is_plugin_active( $plugin_file ) ) {
			$ownership = $this->verify_plugin_ownership( $feature );

			if ( is_wp_error( $ownership ) ) {
				return $ownership;
			}

			$this->update_stored_state( $feature->get_slug(), true );

			return true;
		}

		// Ensure the plugin is on disk — install from ZIP if needed.
		$ensure_result = $this->ensure_installed( $feature );

		if ( is_wp_error( $ensure_result ) ) {
			return $ensure_result;
		}

		// Activate the plugin and update stored state.
		return $this->activate_plugin( $feature );
	}

	/**
	 * Disable a Zip feature: deactivate the plugin.
	 *
	 * Never deletes plugin files — deactivation is safe and reversible.
	 * Idempotent: returns true if the plugin is already inactive.
	 *
	 * @since 3.0.0
	 *
	 * @param Feature $feature Must be a Zip instance.
	 *
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public function disable( Feature $feature ) {
		// Type-guard: Zip_Strategy only handles Zip instances.
		if ( ! $feature instanceof Zip ) {
			return new WP_Error(
				'feature_type_mismatch',
				'Zip_Strategy can only disable Zip instances.'
			);
		}

		$this->load_wp_admin_includes();

		$plugin_file = $feature->get_plugin_file();

		// Idempotent: if already inactive, update stored state and bail.
		if ( ! $this->is_plugin_active( $plugin_file ) ) {
			$this->update_stored_state( $feature->get_slug(), false );

			return true;
		}

		// deactivate_plugins() returns void — it never errors. We verify the
		// actual state afterward to confirm deactivation succeeded.
		deactivate_plugins( $plugin_file, false, false );

		// Verify the plugin is actually inactive now. This catches edge cases
		// where a deactivation hook re-activates the plugin or WordPress's
		// plugin state is otherwise inconsistent.
		// @phpstan-ignore-next-line if.alwaysTrue -- (deactivate_plugins() changes active state via DB side effects invisible to static analysis).
		if ( $this->is_plugin_active( $plugin_file ) ) {
			return new WP_Error(
				'deactivation_failed',
				sprintf(
					'Plugin "%s" is still active after deactivation attempt.',
					$plugin_file
				)
			);
		}

		$this->update_stored_state( $feature->get_slug(), false ); // @phpstan-ignore deadCode.unreachable

		return true;
	}

	/**
	 * Check whether a feature's plugin is currently active.
	 *
	 * The live WordPress plugin state is the source of truth. If the stored
	 * state (wp_options) disagrees with the live state, it is self-healed to
	 * match. This handles edge cases where plugins are activated/deactivated
	 * outside of the feature system (e.g. via the Plugins admin page).
	 *
	 * @since 3.0.0
	 *
	 * @param Feature $feature Must be a Zip instance.
	 *
	 * @return bool
	 */
	public function is_active( Feature $feature ): bool {
		// Type-guard: non-Zip features are never "active" from this strategy's perspective.
		if ( ! $feature instanceof Zip ) {
			return false;
		}

		$this->load_wp_admin_includes();

		$live_active   = $this->is_plugin_active( $feature->get_plugin_file() );
		$stored_active = $this->get_stored_state( $feature->get_slug() );

		// Self-heal: if stored state doesn't match live state, correct it.
		// This is expected to be rare — it only happens when a plugin's state
		// changes outside of the feature system.
		if ( $stored_active !== $live_active ) {
			$this->update_stored_state( $feature->get_slug(), $live_active );

			if ( $this->is_wp_debug() ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log(
					sprintf(
						'[Uplink] Self-healed feature state for "%s": stored=%s, live=%s',
						$feature->get_slug(),
						$stored_active === null ? 'null' : var_export( $stored_active, true ),
						$live_active ? 'true' : 'false'
					)
				);
			}
		}

		return $live_active;
	}

	/**
	 * Sync hook: update stored state when a plugin is activated via WordPress.
	 *
	 * Intended to be wired to the 'activated_plugin' hook by the Provider.
	 * Resolves the plugin_file to a Zip feature via the feature_resolver
	 * callable, then updates stored state to match.
	 *
	 * If no feature_resolver is configured (i.e. the Provider isn't built yet),
	 * this method silently no-ops.
	 *
	 * TODO: Wire this to the 'activated_plugin' hook in Provider once the
	 *       Feature Collection and feature_resolver are built.
	 *
	 * @since 3.0.0
	 *
	 * @param string $plugin       Plugin file path relative to plugins directory.
	 * @param bool   $network_wide Whether the activation was network-wide.
	 *
	 * @return void
	 */
	public function on_plugin_activated( string $plugin, bool $network_wide ): void {
		$feature = $this->resolve_feature( $plugin );

		if ( $feature === null ) {
			return;
		}

		$this->update_stored_state( $feature->get_slug(), true );
	}

	/**
	 * Sync hook: update stored state when a plugin is deactivated via WordPress.
	 *
	 * Intended to be wired to the 'deactivated_plugin' hook by the Provider.
	 * Resolves the plugin_file to a Zip feature via the feature_resolver
	 * callable, then updates stored state to match.
	 *
	 * TODO: Wire this to the 'deactivated_plugin' hook in Provider once the
	 *       Feature Collection and feature_resolver are built.
	 *
	 * @since 3.0.0
	 *
	 * @param string $plugin       Plugin file path relative to plugins directory.
	 * @param bool   $network_wide Whether the deactivation was network-wide.
	 *
	 * @return void
	 */
	public function on_plugin_deactivated( string $plugin, bool $network_wide ): void {
		$feature = $this->resolve_feature( $plugin );

		if ( $feature === null ) {
			return;
		}

		$this->update_stored_state( $feature->get_slug(), false );
	}

	/**
	 * Ensure the plugin is installed on disk, downloading from ZIP if needed.
	 *
	 * Acquires a per-slug transient lock to prevent concurrent installs,
	 * resolves the download link via plugins_api(), runs
	 * Plugin_Upgrader::install(), and verifies the expected plugin_file
	 * exists on disk afterward.
	 *
	 * If the plugin is already installed, returns true immediately (no lock needed).
	 *
	 * @since 3.0.0
	 *
	 * @param Zip $feature The feature whose plugin to ensure is installed.
	 *
	 * @return true|WP_Error True if installed (or already was), WP_Error on failure.
	 */
	private function ensure_installed( Zip $feature ) {
		$plugin_file = $feature->get_plugin_file();

		// Already on disk — verify ownership before treating it as "ours".
		if ( $this->is_plugin_installed( $plugin_file ) ) {
			return $this->verify_plugin_ownership( $feature );
		}

		// Acquire a per-slug transient lock to prevent concurrent installs.
		// Two simultaneous requests could both see "not installed" and race
		// Plugin_Upgrader::install(), causing file conflicts or corruption.
		$lock_key = self::LOCK_PREFIX . $feature->get_plugin_slug();

		if ( ! $this->acquire_lock( $lock_key ) ) {
			return new WP_Error(
				'install_locked',
				sprintf(
					'Another install is in progress for "%s". Try again in a moment.',
					$feature->get_slug()
				)
			);
		}

		try {
			$install_result = $this->install_plugin( $feature );

			if ( is_wp_error( $install_result ) ) {
				return $install_result;
			}

			// Post-install verification: the ZIP's directory structure might not
			// match the expected plugin_file path. Catch this early with a clear
			// error rather than a confusing "plugin not found" during activation.
			// @phpstan-ignore-next-line booleanNot.alwaysTrue -- (install_plugin() creates files on disk; side effects invisible to static analysis).
			if ( ! $this->is_plugin_installed( $plugin_file ) ) {
				return new WP_Error(
					'plugin_not_found',
					sprintf(
						'Plugin file "%s" not found after install. The ZIP may contain a different directory name.',
						$plugin_file
					)
				);
			}

			return true; // @phpstan-ignore deadCode.unreachable
		} finally {
			// Always release the lock, even on early returns or exceptions.
			$this->release_lock( $lock_key );
		}
	}

	/**
	 * Install a plugin via plugins_api() and Plugin_Upgrader.
	 *
	 * Resolves the download link through plugins_api(), which is expected to
	 * be filtered by the Features Provider to return catalog data for known
	 * feature slugs. Uses WP_Ajax_Upgrader_Skin to suppress output.
	 *
	 * @since 3.0.0
	 *
	 * @param Zip $feature The feature whose plugin to install.
	 *
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	private function install_plugin( Zip $feature ) {
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php';
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

		$plugin_info = plugins_api(
			'plugin_information',
			[
				'slug'   => sanitize_key( $feature->get_plugin_slug() ),
				'fields' => [ 'sections' => false ],
			]
		);

		if ( is_wp_error( $plugin_info ) ) {
			return new WP_Error(
				'plugins_api_failed',
				sprintf(
					'plugins_api() failed for "%s": %s',
					$feature->get_slug(),
					$plugin_info->get_error_message()
				)
			);
		}

		if ( empty( $plugin_info->download_link ) ) {
			return new WP_Error(
				'download_link_empty',
				sprintf( 'plugins_api() returned no download_link for "%s".', $feature->get_slug() )
			);
		}

		$skin     = new WP_Ajax_Upgrader_Skin();
		$upgrader = new Plugin_Upgrader( $skin );

		$result = $upgrader->install( Cast::to_string( $plugin_info->download_link ) );

		// Plugin_Upgrader::install() returns true on success or WP_Error on
		// failure. The skin may also collect errors separately.
		if ( is_wp_error( $result ) ) {
			return new WP_Error(
				'install_failed',
				sprintf(
					'Plugin install failed for "%s": %s',
					$feature->get_slug(),
					$result->get_error_message()
				)
			);
		}

		if ( $result !== true ) {
			// Defensive: covers any unexpected falsy return not typed in stubs.
			$skin_errors = $skin->get_errors();
			$message     = $skin_errors->has_errors()
				? $skin_errors->get_error_message()
				: 'Unknown install failure.';

			return new WP_Error(
				'install_failed',
				sprintf( 'Plugin install failed for "%s": %s', $feature->get_slug(), $message )
			);
		}

		return true;
	}

	/**
	 * Activate the plugin for a Zip feature with fatal error protection.
	 *
	 * Runs pre-flight checks (cheap validation) followed by in-process
	 * activation with try/catch Throwable to catch PHP Error subclasses
	 * (ParseError, TypeError, etc.).
	 *
	 * @since 3.0.0
	 *
	 * @param Zip $feature The feature whose plugin to activate.
	 *
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	private function activate_plugin( Zip $feature ) {
		$plugin_file = $feature->get_plugin_file();

		// Pre-flight: cheap validation — fail fast before include.
		$preflight = $this->pre_flight_checks( $plugin_file );

		if ( is_wp_error( $preflight ) ) {
			return $preflight;
		}

		// In-process activation with try/catch Throwable.
		return $this->activate_plugin_in_process( $feature );
	}

	/**
	 * Run cheap pre-flight checks before attempting activation.
	 *
	 * Validates that the plugin file exists and meets WordPress requirements
	 * (PHP version, WP version) without actually loading the plugin.
	 *
	 * @since 3.0.0
	 *
	 * @param string $plugin_file Plugin file path relative to plugins directory.
	 *
	 * @return true|WP_Error True if checks pass, WP_Error on failure.
	 */
	private function pre_flight_checks( string $plugin_file ) {
		$valid = validate_plugin( $plugin_file );

		if ( is_wp_error( $valid ) ) {
			return new WP_Error(
				'preflight_invalid_plugin',
				sprintf(
					'Pre-flight check failed for "%s": %s',
					$plugin_file,
					$valid->get_error_message()
				)
			);
		}

		if ( function_exists( 'validate_plugin_requirements' ) ) {
			$requirements = validate_plugin_requirements( $plugin_file );

			if ( is_wp_error( $requirements ) ) {
				return new WP_Error(
					'preflight_requirements_not_met',
					sprintf(
						'Requirements not met for "%s": %s',
						$plugin_file,
						$requirements->get_error_message()
					)
				);
			}
		}

		return true;
	}

	/**
	 * Activate a plugin in-process with try/catch Throwable protection.
	 *
	 * Catches PHP Error subclasses (ParseError, TypeError, etc.) but cannot
	 * catch exit()/die()/OOM.
	 *
	 * @since 3.0.0
	 *
	 * @param Zip $feature The feature whose plugin to activate.
	 *
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	private function activate_plugin_in_process( Zip $feature ) {
		$plugin_file = $feature->get_plugin_file();

		try {
			$result = activate_plugin( $plugin_file );
		} catch ( Throwable $e ) {
			return new WP_Error(
				'activation_fatal',
				sprintf(
					'Fatal error during activation of "%s": %s',
					$plugin_file,
					Cast::to_string( $e->getMessage() )
				)
			);
		}

		if ( is_wp_error( $result ) ) {
			return new WP_Error(
				'activation_failed',
				sprintf(
					'Activation failed for "%s": %s',
					$plugin_file,
					$result->get_error_message()
				)
			);
		}

		if ( ! $this->is_plugin_active( $plugin_file ) ) {
			return new WP_Error(
				'activation_failed',
				sprintf(
					'Plugin "%s" is not active after activation attempt.',
					$plugin_file
				)
			);
		}

		$this->update_stored_state( $feature->get_slug(), true );

		return true;
	}

	/**
	 * Check whether a plugin is currently active in WordPress.
	 *
	 * Checks both single-site and network activation to handle multisite
	 * environments correctly. A network-activated plugin would be missed by
	 * is_plugin_active() alone.
	 *
	 * @since 3.0.0
	 *
	 * @param string $plugin_file Plugin file path relative to plugins directory.
	 *
	 * @return bool
	 */
	private function is_plugin_active( string $plugin_file ): bool {
		return \is_plugin_active( $plugin_file )
			|| is_plugin_active_for_network( $plugin_file );
	}

	/**
	 * Check whether a plugin is installed on disk.
	 *
	 * Looks for the main plugin file in the WordPress plugins directory.
	 *
	 * @since 3.0.0
	 *
	 * @param string $plugin_file Plugin file path relative to plugins directory.
	 *
	 * @return bool
	 */
	private function is_plugin_installed( string $plugin_file ): bool {
		return file_exists( WP_PLUGIN_DIR . '/' . $plugin_file );
	}

	/**
	 * Verify that an installed plugin belongs to an expected author.
	 *
	 * Reads the plugin's Author header from disk and compares it against the
	 * expected authors from the Zip feature. Any single match is sufficient.
	 * This prevents activating a different developer's plugin that happens to
	 * share the same directory slug.
	 *
	 * @since 3.0.0
	 *
	 * @param Zip $feature The feature whose plugin to verify.
	 *
	 * @return true|WP_Error True if ownership matches, WP_Error on mismatch.
	 */
	private function verify_plugin_ownership( Zip $feature ) {
		$expected_authors = $feature->get_authors();

		if ( $expected_authors === [] ) {
			return true;
		}

		$plugin_data   = get_plugin_data( WP_PLUGIN_DIR . '/' . $feature->get_plugin_file(), false, false );
		$actual_author = trim( $plugin_data['Author'] );

		foreach ( $expected_authors as $expected ) {
			if ( strcasecmp( trim( $expected ), $actual_author ) === 0 ) {
				return true;
			}
		}

		return new WP_Error(
			'plugin_ownership_mismatch',
			sprintf(
				'Plugin at "%s" belongs to a different developer (expected Author: "%s", found: "%s").',
				$feature->get_plugin_file(),
				implode( '" or "', $expected_authors ),
				$actual_author
			)
		);
	}

	/**
	 * Attempt to acquire a transient-based lock for a plugin slug.
	 *
	 * Uses a simple set-if-absent pattern. The transient TTL ensures the lock
	 * auto-expires even if the process crashes without releasing it.
	 *
	 * @since 3.0.0
	 *
	 * @param string $lock_key Transient key for the lock.
	 *
	 * @return bool True if lock acquired, false if already held.
	 */
	private function acquire_lock( string $lock_key ): bool {
		// get_transient returns false if not set. If the lock exists, another
		// install is in progress.
		if ( get_transient( $lock_key ) !== false ) {
			return false;
		}

		// Set the lock with a TTL. There's a tiny race window between the get
		// and set, but transient-based locking is sufficient for preventing
		// the common case of duplicate AJAX requests.
		set_transient( $lock_key, '1', self::LOCK_TTL );

		return true;
	}

	/**
	 * Release a transient-based install lock.
	 *
	 * @since 3.0.0
	 *
	 * @param string $lock_key Transient key for the lock.
	 *
	 * @return void
	 */
	private function release_lock( string $lock_key ): void {
		delete_transient( $lock_key );
	}

	/**
	 * Resolve a plugin file path to a Zip feature via the configured resolver.
	 *
	 * Returns null if no resolver is configured or if the plugin doesn't
	 * correspond to a known feature.
	 *
	 * @since 3.0.0
	 *
	 * @param string $plugin_file Plugin file path relative to plugins directory.
	 *
	 * @return Zip|null
	 */
	private function resolve_feature( string $plugin_file ): ?Zip {
		if ( $this->feature_resolver === null ) {
			return null;
		}

		$resolved = ( $this->feature_resolver )( $plugin_file );

		return $resolved instanceof Zip ? $resolved : null;
	}

	/**
	 * Load WordPress admin includes required for plugin management.
	 *
	 * These files may not be loaded in REST API or AJAX contexts, but are
	 * needed for is_plugin_active(), activate_plugin(), deactivate_plugins(),
	 * and Plugin_Upgrader.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	private function load_wp_admin_includes(): void {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
	}
}
