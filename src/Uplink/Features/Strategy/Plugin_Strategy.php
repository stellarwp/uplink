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
use function get_plugin_data;
use function is_plugin_active;
use function is_plugin_active_for_network;
use function plugins_api;
use function rest_convert_error_to_response;
use function sanitize_key;
use function wp_json_encode;

/**
 * Plugin Strategy — installs, activates, and deactivates WordPress plugins as
 * "features" using ZIP file downloads.
 *
 * The shared enable/disable/is_active/ensure_installed flow is templated by
 * Installable_Strategy. This class provides the WP-specific hooks:
 * - do_install()     → plugins_api() + Plugin_Upgrader
 * - do_activate()    → activate_plugin() with fatal error protection
 * - do_deactivate()  → deactivate_plugins() + verification
 * - verify_ownership → Author header checks (3 cases)
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
class Plugin_Strategy extends Installable_Strategy {

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

	// ── Abstract method implementations ─────────────────────────────────

	/**
	 * @inheritDoc
	 */
	protected function get_type_mismatch_message(): string {
		return __( 'This feature type is not supported by the Plugin installer.', '%TEXTDOMAIN%' );
	}

	/**
	 * Check whether the plugin is currently active in WordPress.
	 *
	 * @since 3.0.0
	 *
	 * @param Feature $feature Already type-guarded as Plugin by the template.
	 *
	 * @return bool
	 */
	protected function check_active( Feature $feature ): bool {
		/** @var Plugin $feature */
		$plugin_file = $feature->get_plugin_file();

		return is_plugin_active( $plugin_file )
			|| is_plugin_active_for_network( $plugin_file );
	}

	/**
	 * Check whether the plugin is installed on disk.
	 *
	 * @since 3.0.0
	 *
	 * @param Feature $feature Already type-guarded as Plugin by the template.
	 *
	 * @return bool
	 */
	protected function check_installed( Feature $feature ): bool {
		/** @var Plugin $feature */
		return file_exists( WP_PLUGIN_DIR . '/' . $feature->get_plugin_file() );
	}

	/**
	 * Install the plugin via plugins_api() and Plugin_Upgrader.
	 *
	 * @since 3.0.0
	 *
	 * @param Feature $feature Already type-guarded as Plugin by the template.
	 *
	 * @return true|WP_Error
	 */
	protected function do_install( Feature $feature ) {
		return $this->install_plugin( $feature );
	}

	/**
	 * Activate the plugin with fatal error protection.
	 *
	 * @since 3.0.0
	 *
	 * @param Feature $feature Already type-guarded as Plugin by the template.
	 *
	 * @return true|WP_Error
	 */
	protected function do_activate( Feature $feature ) {
		return $this->activate_plugin( $feature );
	}

	/**
	 * Deactivate the plugin.
	 *
	 * Never deletes plugin files — deactivation is safe and reversible.
	 * Idempotent: returns true if the plugin is already inactive.
	 *
	 * @since 3.0.0
	 *
	 * @param Feature $feature Already type-guarded as Plugin by the template.
	 *
	 * @return true|WP_Error
	 */
	protected function do_deactivate( Feature $feature ) {
		/** @var Plugin $feature */
		$plugin_file = $feature->get_plugin_file();

		// Idempotent: if already inactive, update stored state and bail.
		if ( ! $this->check_active( $feature ) ) {
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
		if ( $this->check_active( $feature ) ) {
			return new WP_Error(
				Error_Code::DEACTIVATION_FAILED,
				sprintf(
					/* translators: %s: feature name */
					__( '"%s" could not be deactivated. The plugin may have been reactivated by another process.', '%TEXTDOMAIN%' ),
					$feature->get_name()
				)
			);
		}

		$this->update_stored_state( $feature->get_slug(), false ); // @phpstan-ignore deadCode.unreachable (The check above is a double check)

		return true;
	}

	/**
	 * Verify plugin ownership.
	 *
	 * @since 3.0.0
	 *
	 * @param Feature $feature Already type-guarded as Plugin by the template.
	 *
	 * @return true|WP_Error
	 */
	protected function verify_ownership( Feature $feature ) {
		return $this->verify_plugin_ownership( $feature );
	}

	/**
	 * @inheritDoc
	 */
	protected function get_not_found_after_install_error_code(): string {
		return Error_Code::PLUGIN_NOT_FOUND_AFTER_INSTALL;
	}

	// ── Sync hooks ──────────────────────────────────────────────────────

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
		$feature = $this->resolve_plugin_feature( $plugin );

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
		$feature = $this->resolve_plugin_feature( $plugin );

		if ( $feature === null ) {
			return;
		}

		$this->update_stored_state( $feature->get_slug(), false );
	}

	// ── Private helpers ─────────────────────────────────────────────────

	/**
	 * Install a plugin via plugins_api() and Plugin_Upgrader.
	 *
	 * Resolves the download link through plugins_api(), which is expected to
	 * be filtered by the Features Provider to return catalog data for known
	 * feature slugs. Uses WP_Ajax_Upgrader_Skin to suppress output.
	 *
	 * @since 3.0.0
	 *
	 * @param Feature $feature The feature whose plugin to install.
	 *
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	private function install_plugin( Feature $feature ) {
		/** @var Plugin $feature */
		$plugin_info = plugins_api(
			'plugin_information',
			[
				'slug'   => sanitize_key( $feature->get_slug() ),
				'fields' => [ 'sections' => false ],
			]
		);

		if ( is_wp_error( $plugin_info ) ) {
			return new WP_Error(
				Error_Code::PLUGINS_API_FAILED,
				sprintf(
					/* translators: %1$s: feature name, %2$s: error message */
					__( 'Could not retrieve download information for "%1$s": %2$s', '%TEXTDOMAIN%' ),
					$feature->get_name(),
					$plugin_info->get_error_message()
				)
			);
		}

		if ( empty( $plugin_info->download_link ) ) {
			return new WP_Error(
				Error_Code::DOWNLOAD_LINK_MISSING,
				sprintf(
					/* translators: %s: feature name */
					__( 'No download link is available for "%s".', '%TEXTDOMAIN%' ),
					$feature->get_name()
				)
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
					/* translators: %1$s: feature name, %2$s: error message */
					__( 'Installation of "%1$s" failed: %2$s', '%TEXTDOMAIN%' ),
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
				: __( 'An unknown error occurred during installation.', '%TEXTDOMAIN%' );

			return new WP_Error(
				$error_code,
				sprintf(
					/* translators: %1$s: feature name, %2$s: error message */
					__( 'Installation of "%1$s" failed: %2$s', '%TEXTDOMAIN%' ),
					$feature->get_name(),
					$message
				)
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
	 * @param Feature $feature The feature whose plugin to activate.
	 *
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	private function activate_plugin( Feature $feature ) {
		/** @var Plugin $feature */
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
						/* translators: %s: plugin file path */
						__( 'The plugin "%s" called exit/die during activation and terminated the process.', '%TEXTDOMAIN%' ),
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
					/* translators: %1$s: feature name, %2$s: error message */
					__( 'A fatal error occurred while activating "%1$s": %2$s', '%TEXTDOMAIN%' ),
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
					/* translators: %1$s: feature name, %2$s: error message */
					__( 'Activation of "%1$s" failed: %2$s', '%TEXTDOMAIN%' ),
					$feature->get_name(),
					wp_strip_all_tags( $result->get_error_message() )
				)
			);
		}

		if ( ! $this->check_active( $feature ) ) {
			return new WP_Error(
				Error_Code::ACTIVATION_FAILED,
				sprintf(
					/* translators: %s: feature name */
					__( '"%s" did not activate successfully. Please try again.', '%TEXTDOMAIN%' ),
					$feature->get_name()
				)
			);
		}

		$this->update_stored_state( $feature->get_slug(), true );

		return true;
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
	 * @param Feature $feature The feature whose plugin to verify.
	 *
	 * @return true|WP_Error True if ownership matches, WP_Error on mismatch.
	 */
	private function verify_plugin_ownership( Feature $feature ) {
		/** @var Plugin $feature */
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
		$plugin_dirname = dirname( $plugin_file );
		$plugin_dir     = WP_PLUGIN_DIR . '/' . $plugin_dirname;

		if ( is_dir( $plugin_dir ) ) {
			return $this->check_folder_for_foreign_plugins( $plugin_dir, $plugin_dirname, $expected_authors );
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
				/* translators: %1$s: plugin file path, %2$s: expected author(s), %3$s: actual author */
				__( 'The installed plugin at "%1$s" appears to belong to a different developer (expected "%2$s", found "%3$s") and cannot be managed as a feature.', '%TEXTDOMAIN%' ),
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
						/* translators: %1$s: plugin folder, %2$s: found plugin name, %3$s: found author, %4$s: expected author(s) */
						__( 'The folder "%1$s" is already occupied by a plugin from a different developer (found "%2$s" by "%3$s", expected author "%4$s"). The feature cannot be installed here.', '%TEXTDOMAIN%' ),
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
	private function resolve_plugin_feature( string $plugin_file ): ?Plugin {
		$resolved = $this->resolve_feature( $plugin_file );

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
	protected function load_wp_admin_includes(): void {
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
