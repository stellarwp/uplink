<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Strategy;

use StellarWP\Uplink\Features\Error_Code;
use StellarWP\Uplink\Features\Types\Feature;
use StellarWP\Uplink\Features\Types\Plugin;
use StellarWP\Uplink\Utils\Cast;
use WP_Error;
use Throwable;
use WP_Ajax_Upgrader_Skin;
use Plugin_Upgrader;

use function activate_plugin;
use function deactivate_plugins;
use function delete_transient;
use function get_plugin_data;
use function get_transient;
use function is_plugin_active;
use function is_plugin_active_for_network;
use function plugins_api;
use function rest_convert_error_to_response;
use function sanitize_key;
use function set_transient;
use function wp_json_encode;
/**
 * Plugin Strategy — installs, activates, and deactivates WordPress plugins as
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
class Plugin_Strategy extends Abstract_Strategy {

	/**
	 * Transient lock TTL in seconds.
	 *
	 * 120 seconds is generous enough to cover slow downloads on shared hosting,
	 * but short enough that a crashed install won't block retries for long.
	 *
	 * @since 3.0.0
	 */
	private const LOCK_TTL = MINUTE_IN_SECONDS * 2;

	/**
	 * Transient key prefix for install locks.
	 *
	 * Full key: stellarwp_uplink_install_lock_{slug}
	 *
	 * @since 3.0.0
	 */
	private const LOCK_PREFIX = 'stellarwp_uplink_install_lock_';

	/**
	 * WordPress error codes that indicate PHP or WP version requirements are not met.
	 *
	 * Install path: emitted by Plugin_Upgrader::check_package() and captured by the skin.
	 * Activation path: returned directly by activate_plugin() via validate_plugin_requirements().
	 *
	 * @since 3.0.0
	 *
	 * @var string[]
	 */
	private const REQUIREMENTS_ERROR_CODES = [
		'incompatible_php_required_version',
		'incompatible_wp_required_version',
		'plugin_php_incompatible',
		'plugin_wp_incompatible',
		'plugin_wp_php_incompatible',
	];

	/**
	 * Optional callable that resolves a plugin_file string to a Plugin feature.
	 *
	 * Signature: fn(string $plugin_file): ?Plugin
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
	 * Construct the Plugin_Strategy.
	 *
	 * @since 3.0.0
	 *
	 * @param callable|null $feature_resolver Optional. Resolves a plugin_file
	 *                                        string to a Plugin instance.
	 */
	public function __construct( ?callable $feature_resolver = null ) {
		$this->feature_resolver = $feature_resolver;
	}

	/**
	 * Enable a Plugin feature: install (if needed) and activate the plugin.
	 *
	 * Idempotent: returns true if the plugin is already active. Uses a
	 * transient lock to prevent concurrent installs of the same plugin.
	 *
	 * @since 3.0.0
	 *
	 * @param Feature $feature Must be a Plugin instance.
	 *
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public function enable( Feature $feature ) {
		// Type-guard: Plugin_Strategy only handles Plugin instances.
		if ( ! $feature instanceof Plugin ) {
			return new WP_Error(
				Error_Code::FEATURE_TYPE_MISMATCH,
				'This feature type is not supported by the Plugin installer.'
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

		// Verify ownership before attempting installation. This catches
		// cases where the plugin folder is already occupied by a different
		// developer's plugin. If nothing is on disk yet, this returns true
		// (no conflict) and we proceed to install.
		$ownership = $this->verify_plugin_ownership( $feature );

		if ( is_wp_error( $ownership ) ) {
			return $ownership;
		}

		// Ensure the plugin is on disk — install from ZIP if needed.
		$ensure_result = $this->ensure_installed( $feature );

		if ( is_wp_error( $ensure_result ) ) {
			return $ensure_result;
		}

		// Verify ownership after installation. A fresh download may contain
		// a plugin from an unexpected author.
		$ownership = $this->verify_plugin_ownership( $feature );

		if ( is_wp_error( $ownership ) ) {
			return $ownership;
		}

		// Activate the plugin and update stored state.
		return $this->activate_plugin( $feature );
	}

	/**
	 * Disable a Plugin feature: deactivate the plugin.
	 *
	 * Never deletes plugin files — deactivation is safe and reversible.
	 * Idempotent: returns true if the plugin is already inactive.
	 *
	 * @since 3.0.0
	 *
	 * @param Feature $feature Must be a Plugin instance.
	 *
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public function disable( Feature $feature ) {
		// Type-guard: Plugin_Strategy only handles Plugin instances.
		if ( ! $feature instanceof Plugin ) {
			return new WP_Error(
				Error_Code::FEATURE_TYPE_MISMATCH,
				'This feature type is not supported by the Plugin installer.'
			);
		}

		$this->load_wp_admin_includes();

		// Refuse to touch a plugin that belongs to a different developer.
		$ownership = $this->verify_plugin_ownership( $feature );

		if ( is_wp_error( $ownership ) ) {
			return $ownership;
		}

		$plugin_file = $feature->get_plugin_file();

		// Idempotent: if already inactive, update stored state and bail.
		if ( ! $this->is_plugin_active( $plugin_file ) ) {
			$this->update_stored_state( $feature->get_slug(), false );

			return true;
		}

		// deactivate_plugins() returns void — it never errors. We verify the
		// actual state afterward to confirm deactivation succeeded.
		deactivate_plugins( $plugin_file, false, is_plugin_active_for_network( $plugin_file ) );

		// Verify the plugin is actually inactive now. This catches edge cases
		// where a deactivation hook re-activates the plugin or WordPress's
		// plugin state is otherwise inconsistent.
		// @phpstan-ignore-next-line if.alwaysTrue -- (deactivate_plugins() changes active state via DB side effects invisible to static analysis).
		if ( $this->is_plugin_active( $plugin_file ) ) {
			return new WP_Error(
				Error_Code::DEACTIVATION_FAILED,
				sprintf(
					'"%s" could not be deactivated. The plugin may have been reactivated by another process.',
					$feature->get_name()
				)
			);
		}

		$this->update_stored_state( $feature->get_slug(), false ); // @phpstan-ignore deadCode.unreachable (The check above is a double check)

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
	 * @param Feature $feature Must be a Plugin instance.
	 *
	 * @return bool
	 */
	public function is_active( Feature $feature ): bool {
		// Type-guard: non-Plugin features are never "active" from this strategy's perspective.
		if ( ! $feature instanceof Plugin ) {
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
	 * Resolves the plugin_file to a Plugin feature via the feature_resolver
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
	 * Resolves the plugin_file to a Plugin feature via the feature_resolver
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
	 * @param Plugin $feature The feature whose plugin to ensure is installed.
	 *
	 * @return true|WP_Error True if installed (or already was), WP_Error on failure.
	 */
	private function ensure_installed( Plugin $feature ) {
		$plugin_file = $feature->get_plugin_file();

		// Already on disk — ready for activation. Ownership is verified
		// by the caller (enable()) after this method returns.
		if ( $this->is_plugin_installed( $plugin_file ) ) {
			return true;
		}

		// Acquire a per-slug transient lock to prevent concurrent installs.
		// Two simultaneous requests could both see "not installed" and race
		// Plugin_Upgrader::install(), causing file conflicts or corruption.
		$lock_key = self::LOCK_PREFIX . $feature->get_plugin_slug();

		if ( ! $this->acquire_lock( $lock_key ) ) {
			return new WP_Error(
				Error_Code::INSTALL_LOCKED,
				sprintf(
					'Another installation is already in progress for "%s". Please try again in a few moments.',
					$feature->get_name()
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
					Error_Code::PLUGIN_NOT_FOUND_AFTER_INSTALL,
					sprintf(
						'The plugin file was not found after installing "%s". The downloaded package may have an unexpected directory structure.',
						$feature->get_name()
					)
				);
			}

			return true; // @phpstan-ignore deadCode.unreachable (The check above is a double check)
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
	 * @param Plugin $feature The feature whose plugin to install.
	 *
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	private function install_plugin( Plugin $feature ) {
		$plugin_info = plugins_api(
			'plugin_information',
			[
				'slug'   => sanitize_key( $feature->get_plugin_slug() ),
				'fields' => [ 'sections' => false ],
			]
		);

		if ( is_wp_error( $plugin_info ) ) {
			return new WP_Error(
				Error_Code::PLUGINS_API_FAILED,
				sprintf(
					'Could not retrieve download information for "%s": %s',
					$feature->get_name(),
					$plugin_info->get_error_message()
				)
			);
		}

		if ( empty( $plugin_info->download_link ) ) {
			return new WP_Error(
				Error_Code::DOWNLOAD_LINK_MISSING,
				sprintf( 'No download link is available for "%s".', $feature->get_name() )
			);
		}

		$skin     = new WP_Ajax_Upgrader_Skin();
		$upgrader = new Plugin_Upgrader( $skin );

		$result = $upgrader->install( Cast::to_string( $plugin_info->download_link ) );

		// Plugin_Upgrader::install() returns true on success or WP_Error on
		// failure. The skin may also collect errors separately.
		if ( is_wp_error( $result ) ) {
			return new WP_Error(
				Error_Code::INSTALL_FAILED,
				sprintf(
					'Installation of "%s" failed: %s',
					$feature->get_name(),
					$result->get_error_message()
				)
			);
		}

		if ( $result !== true ) {
			// Defensive: covers any unexpected falsy return not typed in stubs.
			// When check_package() rejects a plugin for PHP/WP version requirements,
			// Plugin_Upgrader::install() returns a falsy value (empty array) rather
			// than a WP_Error. The specific error is captured in the skin.
			$skin_errors = $skin->get_errors();
			$error_code  = Error_Code::INSTALL_FAILED;

			if ( $skin_errors->has_errors() && array_intersect( $skin_errors->get_error_codes(), self::REQUIREMENTS_ERROR_CODES ) ) {
				$error_code = Error_Code::REQUIREMENTS_NOT_MET;
			}

			// Use the skin's get_error_messages() which concatenates the error
			// message with its data, providing the specific reason (e.g.
			// "The PHP version on your server is X, however the plugin requires Y").
			$message = $skin_errors->has_errors()
				? $skin->get_error_messages()
				: 'An unknown error occurred during installation.';

			return new WP_Error(
				$error_code,
				sprintf( 'Installation of "%s" failed: %s', $feature->get_name(), $message )
			);
		}

		return true;
	}

	/**
	 * Activate the plugin for a Plugin feature with fatal error protection.
	 *
	 * Uses try/catch Throwable to catch PHP Error subclasses
	 * (ParseError, TypeError, etc.) and a shutdown function with output
	 * buffering to handle die()/exit() calls during plugin activation.
	 *
	 * @since 3.0.0
	 *
	 * @param Plugin $feature The feature whose plugin to activate.
	 *
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	private function activate_plugin( Plugin $feature ) {
		$plugin_file = $feature->get_plugin_file();
		$completed   = false;
		$die_output  = '';

		// Register a shutdown function to handle die()/exit() during activation.
		// PHP runs shutdown functions after die(), so we can capture the output
		// and send a proper JSON error response instead of raw text.
		register_shutdown_function(
			static function () use ( $plugin_file, &$completed, &$die_output ) {
				if ( $completed ) { // @phpstan-ignore-line booleanNot.alwaysFalse -- completed is set to true in the try block.
					return;
				}

				// Pick up anything from buffers that wp_ob_end_flush_all didn't reach.
				while ( ob_get_level() > 0 ) {
					$die_output .= ob_get_clean() ?: '';
				}

				$error = new WP_Error(
					Error_Code::ACTIVATION_FATAL,
					sprintf(
						'The plugin "%s" called exit/die during activation and terminated the process.',
						$plugin_file
					),
					[ 'status' => 422 ]
				);

				if ( $die_output !== '' ) {
					$error->add(
						Error_Code::ACTIVATION_FATAL,
						substr( $die_output, 0, 500 )
					);
				}

				$response = rest_convert_error_to_response( $error );

				if ( ! headers_sent() ) {
					http_response_code( $response->get_status() );
					header( 'Content-Type: application/json; charset=UTF-8' );
				}

				echo wp_json_encode( $response->get_data() );
			}
		);

		// Start output buffering with a callback to intercept die() output.
		// WordPress registers wp_ob_end_flush_all as a shutdown function
		// (before ours), which flushes all buffer levels. Without a callback,
		// the die() output would reach the client before our shutdown function
		// runs. The callback intercepts the flush, captures the output, and
		// returns '' to suppress it.
		$ob_level_before = ob_get_level();

		ob_start(
			static function ( $buffer ) use ( &$completed, &$die_output ) {
				if ( $completed ) { // @phpstan-ignore-line booleanNot.alwaysFalse -- completed is set to true in the try block.
					return $buffer;
				}

				$die_output .= Cast::to_string( $buffer );

				return '';
			}
		);

		try {
			$result = activate_plugin( $plugin_file );
		} catch ( Throwable $e ) {
			$completed = true;

			// Clean any buffers added during activation (e.g. WordPress's own
			// ob_start inside activate_plugin()) plus our own buffer.
			while ( ob_get_level() > $ob_level_before ) {
				ob_end_clean();
			}

			return new WP_Error(
				Error_Code::ACTIVATION_FATAL,
				sprintf(
					'A fatal error occurred while activating "%s": %s',
					$feature->get_name(),
					Cast::to_string( $e->getMessage() )
				)
			);
		}

		$completed = true;

		// Clean any buffers added during activation plus our own buffer.
		while ( ob_get_level() > $ob_level_before ) {
			ob_end_clean();
		}

		if ( is_wp_error( $result ) ) {
			$error_code = in_array( $result->get_error_code(), self::REQUIREMENTS_ERROR_CODES, true )
				? Error_Code::REQUIREMENTS_NOT_MET
				: Error_Code::ACTIVATION_FAILED;

			return new WP_Error(
				$error_code,
				sprintf(
					'Activation of "%s" failed: %s',
					$feature->get_name(),
					wp_strip_all_tags( $result->get_error_message() )
				)
			);
		}

		if ( ! $this->is_plugin_active( $plugin_file ) ) {
			return new WP_Error(
				Error_Code::ACTIVATION_FAILED,
				sprintf(
					'"%s" did not activate successfully. Please try again.',
					$feature->get_name()
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
		return is_plugin_active( $plugin_file )
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
	 * Verify that the plugin directory is not occupied by a different developer.
	 *
	 * Handles three cases:
	 * 1. The exact plugin file exists — checks its Author header.
	 * 2. The folder exists but the expected file doesn't — scans for other
	 *    plugin files in the folder and checks their Author headers.
	 * 3. Neither exists — no conflict, returns true (fresh install).
	 *
	 * This prevents both activating a different developer's plugin that shares
	 * the same file path AND installing over a different developer's plugin
	 * that occupies the same directory with a different main file.
	 *
	 * @since 3.0.0
	 *
	 * TODO: We probably should move it to another place so we can use it during the WP plugins update setup as well.
	 *
	 * @param Plugin $feature The feature whose plugin to verify.
	 *
	 * @return true|WP_Error True if ownership matches, WP_Error on mismatch.
	 */
	private function verify_plugin_ownership( Plugin $feature ) {
		$expected_authors = $feature->get_authors();

		if ( $expected_authors === [] ) {
			return true;
		}

		$plugin_file = $feature->get_plugin_file();
		$full_path   = WP_PLUGIN_DIR . '/' . $plugin_file;

		// Case 1: the exact file exists — check its author directly.
		if ( file_exists( $full_path ) ) {
			return $this->check_file_author( $full_path, $plugin_file, $expected_authors );
		}

		// Case 2: the folder exists but our specific file doesn't.
		// Another developer's plugin may occupy the same directory.
		$plugin_dir = WP_PLUGIN_DIR . '/' . $feature->get_plugin_directory();

		if ( is_dir( $plugin_dir ) ) {
			return $this->check_folder_for_foreign_plugins( $plugin_dir, $feature->get_plugin_directory(), $expected_authors );
		}

		// Case 3: neither the file nor the folder exists — no conflict.
		return true;
	}

	/**
	 * Check whether a specific plugin file's Author header matches expected authors.
	 *
	 * @since 3.0.0
	 *
	 * @param string   $full_path        Absolute path to the plugin file.
	 * @param string   $plugin_file      Plugin file path relative to plugins directory.
	 * @param string[] $expected_authors  Expected author names.
	 *
	 * @return true|WP_Error True if author matches, WP_Error on mismatch.
	 */
	private function check_file_author( string $full_path, string $plugin_file, array $expected_authors ) {
		$plugin_data   = get_plugin_data( $full_path, false, false );
		$actual_author = trim( $plugin_data['Author'] );

		foreach ( $expected_authors as $expected ) {
			if ( strcasecmp( trim( $expected ), $actual_author ) === 0 ) {
				return true;
			}
		}

		return new WP_Error(
			Error_Code::PLUGIN_OWNERSHIP_MISMATCH,
			sprintf(
				'The installed plugin at "%s" appears to belong to a different developer (expected "%s", found "%s") and cannot be managed as a feature.',
				$plugin_file,
				implode( '" or "', $expected_authors ),
				$actual_author
			)
		);
	}

	/**
	 * Scan a plugin directory for plugin files from a different developer.
	 *
	 * Used when the expected plugin file doesn't exist but the folder does,
	 * indicating another plugin may occupy the directory.
	 *
	 * @since 3.0.0
	 *
	 * @param string   $plugin_dir       Absolute path to the plugin directory.
	 * @param string   $plugin_slug      The plugin directory slug (e.g. "test-feature").
	 * @param string[] $expected_authors  Expected author names.
	 *
	 * @return true|WP_Error True if no foreign plugins found, WP_Error on conflict.
	 */
	private function check_folder_for_foreign_plugins( string $plugin_dir, string $plugin_slug, array $expected_authors ) {
		$php_files = glob( $plugin_dir . '/*.php' );

		if ( $php_files === false ) {
			return true;
		}

		foreach ( $php_files as $php_file ) {
			$data = get_plugin_data( $php_file, false, false );

			// Skip files without a Plugin Name header — they aren't plugins.
			if ( empty( $data['Name'] ) ) {
				continue;
			}

			$actual_author = trim( $data['Author'] );
			$is_owned      = false;

			foreach ( $expected_authors as $expected ) {
				if ( strcasecmp( trim( $expected ), $actual_author ) === 0 ) {
					$is_owned = true;
					break;
				}
			}

			if ( ! $is_owned ) {
				return new WP_Error(
					Error_Code::PLUGIN_OWNERSHIP_MISMATCH,
					sprintf(
						'The folder "%s" is already occupied by a plugin from a different developer (found "%s" by "%s", expected author "%s"). The feature cannot be installed here.',
						$plugin_slug,
						$data['Name'],
						$actual_author,
						implode( '" or "', $expected_authors )
					)
				);
			}
		}

		return true;
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
	 * Resolve a plugin file path to a Plugin feature via the configured resolver.
	 *
	 * Returns null if no resolver is configured or if the plugin doesn't
	 * correspond to a known feature.
	 *
	 * @since 3.0.0
	 *
	 * @param string $plugin_file Plugin file path relative to plugins directory.
	 *
	 * @return Plugin|null
	 */
	private function resolve_feature( string $plugin_file ): ?Plugin {
		if ( $this->feature_resolver === null ) {
			return null;
		}

		$resolved = ( $this->feature_resolver )( $plugin_file );

		return $resolved instanceof Plugin ? $resolved : null;
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

		if ( ! function_exists( 'plugins_api' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		}

		if ( ! class_exists( 'Plugin_Upgrader' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}

		if ( ! class_exists( 'WP_Ajax_Upgrader_Skin' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php';
		}
	}
}
