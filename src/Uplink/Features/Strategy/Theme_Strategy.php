<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Strategy;

use StellarWP\Uplink\Features\Error_Code;
use StellarWP\Uplink\Features\Types\Feature;
use StellarWP\Uplink\Features\Types\Theme;
use StellarWP\Uplink\Utils\Cast;
use WP_Error;
use WP_Ajax_Upgrader_Skin;
use Theme_Upgrader;
use WP_Theme;

use function sanitize_key;
use function switch_theme;
use function themes_api;
use function wp_get_theme;

/**
 * Theme Strategy — installs and activates WordPress themes as "features".
 *
 * This strategy handles the full lifecycle:
 * - enable()    → install (if needed) + switch_theme()
 * - disable()   → returns error if theme is active (WP needs one active theme),
 *                  otherwise updates stored state
 * - is_active() → live WP check with self-healing stored state
 *
 * Unlike plugins, themes load on the next request after switch_theme() — no
 * fatal-error protection is needed at switch time.
 *
 * Sync hook: on_theme_switch() is wired to the 'switch_theme' action by the
 * Provider layer to keep stored state in sync when themes are switched outside
 * the feature system (e.g. via Appearance → Themes).
 *
 * @since 3.0.0
 */
class Theme_Strategy extends Installable_Strategy {

	/**
	 * Enable a Theme feature: install (if needed) and switch to the theme.
	 *
	 * Idempotent: returns true if the theme is already active. Uses a
	 * transient lock to prevent concurrent installs of the same theme.
	 *
	 * @since 3.0.0
	 *
	 * @param Feature $feature Must be a Theme instance.
	 *
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public function enable( Feature $feature ) {
		if ( ! $feature instanceof Theme ) {
			return new WP_Error(
				Error_Code::FEATURE_TYPE_MISMATCH,
				'This feature type is not supported by the Theme installer.'
			);
		}

		$this->load_wp_admin_includes();

		$stylesheet = $feature->get_stylesheet();

		// Idempotent: if the theme is already active, verify ownership and bail.
		if ( $this->is_theme_active( $stylesheet ) ) {
			$ownership = $this->verify_theme_ownership( $feature );

			if ( is_wp_error( $ownership ) ) {
				return $ownership;
			}

			$this->update_stored_state( $feature->get_slug(), true );

			return true;
		}

		// Verify ownership before attempting installation.
		$ownership = $this->verify_theme_ownership( $feature );

		if ( is_wp_error( $ownership ) ) {
			return $ownership;
		}

		// Ensure the theme is on disk — install from ZIP if needed.
		$ensure_result = $this->ensure_installed( $feature );

		if ( is_wp_error( $ensure_result ) ) {
			return $ensure_result;
		}

		// Verify ownership after installation.
		$ownership = $this->verify_theme_ownership( $feature );

		if ( is_wp_error( $ownership ) ) {
			return $ownership;
		}

		// Switch to the theme.
		switch_theme( $stylesheet );

		// Verify switch took effect.
		// @phpstan-ignore-next-line booleanNot.alwaysTrue -- (switch_theme() changes active state via DB side effects invisible to static analysis).
		if ( ! $this->is_theme_active( $stylesheet ) ) {
			return new WP_Error(
				Error_Code::ACTIVATION_FAILED,
				sprintf(
					'"%s" did not activate successfully. Please try again.',
					$feature->get_name()
				)
			);
		}

		$this->update_stored_state( $feature->get_slug(), true ); // @phpstan-ignore deadCode.unreachable (The check above is a double check)

		return true;
	}

	/**
	 * Disable a Theme feature.
	 *
	 * WordPress always needs exactly one active theme. If the theme IS the
	 * active theme, returns a WP_Error — switching away must be done by
	 * enabling a different theme. If the theme is NOT active, updates stored
	 * state to false.
	 *
	 * @since 3.0.0
	 *
	 * @param Feature $feature Must be a Theme instance.
	 *
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public function disable( Feature $feature ) {
		if ( ! $feature instanceof Theme ) {
			return new WP_Error(
				Error_Code::FEATURE_TYPE_MISMATCH,
				'This feature type is not supported by the Theme installer.'
			);
		}

		$this->load_wp_admin_includes();

		$stylesheet = $feature->get_stylesheet();

		// Verify ownership before touching state.
		$ownership = $this->verify_theme_ownership( $feature );

		if ( is_wp_error( $ownership ) ) {
			return $ownership;
		}

		// WordPress always needs an active theme — cannot deactivate.
		if ( $this->is_theme_active( $stylesheet ) ) {
			return new WP_Error(
				Error_Code::THEME_IS_ACTIVE,
				sprintf(
					'"%s" is the active theme and cannot be deactivated. Switch to a different theme first.',
					$feature->get_name()
				)
			);
		}

		$this->update_stored_state( $feature->get_slug(), false );

		return true;
	}

	/**
	 * Check whether a feature's theme is currently active.
	 *
	 * The live WordPress theme state is the source of truth. If the stored
	 * state (wp_options) disagrees with the live state, it is self-healed.
	 *
	 * @since 3.0.0
	 *
	 * @param Feature $feature Must be a Theme instance.
	 *
	 * @return bool
	 */
	public function is_active( Feature $feature ): bool {
		if ( ! $feature instanceof Theme ) {
			return false;
		}

		$this->load_wp_admin_includes();

		$live_active   = $this->is_theme_active( $feature->get_stylesheet() );
		$stored_active = $this->get_stored_state( $feature->get_slug() );

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
	 * Sync hook: update stored state when the active theme is switched.
	 *
	 * Wired to the 'switch_theme' action by the Provider. Updates stored
	 * state for both the old theme (→false) and the new theme (→true) if
	 * they correspond to known features.
	 *
	 * @since 3.0.0
	 *
	 * @param string   $new_name  Name of the new theme.
	 * @param WP_Theme $new_theme The new theme object.
	 * @param WP_Theme $old_theme The old theme object.
	 *
	 * @return void
	 */
	public function on_theme_switch( string $new_name, WP_Theme $new_theme, WP_Theme $old_theme ): void {
		$old_feature = $this->resolve_theme_feature( $old_theme->get_stylesheet() );

		if ( $old_feature !== null ) {
			$this->update_stored_state( $old_feature->get_slug(), false );
		}

		$new_feature = $this->resolve_theme_feature( $new_theme->get_stylesheet() );

		if ( $new_feature !== null ) {
			$this->update_stored_state( $new_feature->get_slug(), true );
		}
	}

	/**
	 * Ensure the theme is installed on disk, downloading from ZIP if needed.
	 *
	 * @since 3.0.0
	 *
	 * @param Theme $feature The feature whose theme to ensure is installed.
	 *
	 * @return true|WP_Error True if installed (or already was), WP_Error on failure.
	 */
	private function ensure_installed( Theme $feature ) {
		$stylesheet = $feature->get_stylesheet();

		// Already on disk — ready for activation.
		if ( $this->is_theme_installed( $stylesheet ) ) {
			return true;
		}

		$lock_key = $this->build_lock_key( $stylesheet );

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
			$install_result = $this->install_theme( $feature );

			if ( is_wp_error( $install_result ) ) {
				return $install_result;
			}

			// @phpstan-ignore-next-line booleanNot.alwaysTrue -- (install_theme() creates files on disk; side effects invisible to static analysis).
			if ( ! $this->is_theme_installed( $stylesheet ) ) {
				return new WP_Error(
					Error_Code::THEME_NOT_FOUND_AFTER_INSTALL,
					sprintf(
						'The theme was not found after installing "%s". The downloaded package may have an unexpected directory structure.',
						$feature->get_name()
					)
				);
			}

			return true; // @phpstan-ignore deadCode.unreachable (The check above is a double check)
		} finally {
			$this->release_lock( $lock_key );
		}
	}

	/**
	 * Install a theme via themes_api() and Theme_Upgrader.
	 *
	 * @since 3.0.0
	 *
	 * @param Theme $feature The feature whose theme to install.
	 *
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	private function install_theme( Theme $feature ) {
		$theme_info = themes_api(
			'theme_information',
			[
				'slug'   => sanitize_key( $feature->get_stylesheet() ),
				'fields' => [ 'sections' => false ],
			]
		);

		if ( is_wp_error( $theme_info ) ) {
			return new WP_Error(
				Error_Code::THEMES_API_FAILED,
				sprintf(
					'Could not retrieve download information for "%s": %s',
					$feature->get_name(),
					$theme_info->get_error_message()
				)
			);
		}

		if ( empty( $theme_info->download_link ) ) {
			return new WP_Error(
				Error_Code::DOWNLOAD_LINK_MISSING,
				sprintf( 'No download link is available for "%s".', $feature->get_name() )
			);
		}

		$skin     = new WP_Ajax_Upgrader_Skin();
		$upgrader = new Theme_Upgrader( $skin );

		$result = $upgrader->install(
			Cast::to_string( $theme_info->download_link )
		);

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
			$skin_errors = $skin->get_errors();

			$message = $skin_errors->has_errors()
				? $skin->get_error_messages()
				: 'An unknown error occurred during installation.';

			return new WP_Error(
				Error_Code::INSTALL_FAILED,
				sprintf( 'Installation of "%s" failed: %s', $feature->get_name(), $message )
			);
		}

		return true;
	}

	/**
	 * Check whether a theme is currently active in WordPress.
	 *
	 * @since 3.0.0
	 *
	 * @param string $stylesheet Theme stylesheet (directory name).
	 *
	 * @return bool
	 */
	private function is_theme_active( string $stylesheet ): bool {
		return get_stylesheet() === $stylesheet;
	}

	/**
	 * Check whether a theme is installed on disk.
	 *
	 * @since 3.0.0
	 *
	 * @param string $stylesheet Theme stylesheet (directory name).
	 *
	 * @return bool
	 */
	private function is_theme_installed( string $stylesheet ): bool {
		$theme = wp_get_theme( $stylesheet );

		return $theme->exists();
	}

	/**
	 * Verify that the installed theme belongs to an expected author.
	 *
	 * @since 3.0.0
	 *
	 * @param Theme $feature The feature whose theme to verify.
	 *
	 * @return true|WP_Error True if ownership matches or no theme on disk, WP_Error on mismatch.
	 */
	private function verify_theme_ownership( Theme $feature ) {
		$expected_authors = $feature->get_authors();

		if ( $expected_authors === [] ) {
			return true;
		}

		$stylesheet = $feature->get_stylesheet();
		$theme      = wp_get_theme( $stylesheet );

		// Theme is not installed — no conflict.
		if ( ! $theme->exists() ) {
			return true;
		}

		$actual_author = trim( Cast::to_string( $theme->get( 'Author' ) ) );

		foreach ( $expected_authors as $expected ) {
			if ( strcasecmp( trim( $expected ), $actual_author ) === 0 ) {
				return true;
			}
		}

		return new WP_Error(
			Error_Code::THEME_OWNERSHIP_MISMATCH,
			sprintf(
				'The installed theme "%s" appears to belong to a different developer (expected "%s", found "%s") and cannot be managed as a feature.',
				$stylesheet,
				implode( '" or "', $expected_authors ),
				$actual_author
			)
		);
	}

	/**
	 * Resolve a stylesheet to a Theme feature via the configured resolver.
	 *
	 * @since 3.0.0
	 *
	 * @param string $stylesheet Theme stylesheet (directory name).
	 *
	 * @return Theme|null
	 */
	private function resolve_theme_feature( string $stylesheet ): ?Theme {
		$resolved = $this->resolve_feature( $stylesheet );

		return $resolved instanceof Theme ? $resolved : null;
	}

	/**
	 * Load WordPress admin includes required for theme management.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	protected function load_wp_admin_includes(): void {
		if ( ! function_exists( 'themes_api' ) ) {
			require_once ABSPATH . 'wp-admin/includes/theme.php';
		}

		if ( ! class_exists( 'Theme_Upgrader' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}

		if ( ! class_exists( 'WP_Ajax_Upgrader_Skin' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php';
		}
	}
}
