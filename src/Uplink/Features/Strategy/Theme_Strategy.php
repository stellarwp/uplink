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
use function themes_api;
use function wp_get_theme;

/**
 * Theme Strategy — installs WordPress themes as "features".
 *
 * The shared enable/disable/is_active/ensure_installed flow is templated by
 * Installable_Strategy. This class provides the WP-specific hooks:
 * - do_install()     → themes_api() + Theme_Upgrader
 * - do_activate()    → update stored state only (no switch_theme)
 * - do_deactivate()  → update stored state to false
 * - verify_ownership → Author header check via wp_get_theme()
 *
 * Unlike plugins, themes are not activated or deactivated through the feature
 * system. Enabling a theme installs it and marks it as enabled in stored state.
 * Users activate themes through WordPress's Appearance → Themes UI.
 *
 * The is_active check requires both: theme installed on disk AND stored state
 * says enabled. If a theme is manually deleted, stored state self-heals to
 * false on the next check. A theme existing on disk does NOT override an
 * explicit disable — stored state is the authority for the enabled/disabled
 * toggle.
 *
 * Sync hook: on_theme_switch() is wired to the 'switch_theme' action by the
 * Provider layer to keep stored state in sync when themes are switched outside
 * the feature system (e.g. via Appearance → Themes).
 *
 * @since 3.0.0
 */
class Theme_Strategy extends Installable_Strategy {

	// ── Abstract method implementations ─────────────────────────────────

	/**
	 * @inheritDoc
	 */
	protected function get_feature_class(): string {
		return Theme::class;
	}

	/**
	 * @inheritDoc
	 */
	protected function get_type_mismatch_message(): string {
		return __( 'This feature type is not supported by the Theme installer.', '%TEXTDOMAIN%' );
	}

	/**
	 * @inheritDoc
	 */
	protected function get_wp_identifier( Feature $feature ): string {
		return $feature->get_slug();
	}

	/**
	 * Check whether the theme is "active" — for themes, this means installed on disk.
	 *
	 * Unlike plugins where "active" means currently running, for themes "active"
	 * means the theme is installed and available for the user to activate through
	 * the WordPress UI.
	 *
	 * @since 3.0.0
	 *
	 * @param string $identifier The theme stylesheet (directory name).
	 *
	 * @return bool
	 */
	protected function check_active( string $identifier ): bool {
		return wp_get_theme( $identifier )->exists();
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $identifier The theme stylesheet (directory name).
	 */
	protected function check_installed( string $identifier ): bool {
		return wp_get_theme( $identifier )->exists();
	}

	/**
	 * Install the theme via themes_api() and Theme_Upgrader.
	 *
	 * @since 3.0.0
	 *
	 * @param Feature $feature Already type-guarded as Theme by the template.
	 *
	 * @return true|WP_Error
	 */
	protected function do_install( Feature $feature ) {
		return $this->install_theme( $feature );
	}

	/**
	 * Mark the theme as enabled in stored state.
	 *
	 * Unlike plugins, themes are not activated through the feature system.
	 * The theme is installed and ready for the user to activate through
	 * WordPress's Appearance → Themes UI.
	 *
	 * @since 3.0.0
	 *
	 * @param Feature $feature Already type-guarded as Theme by the template.
	 *
	 * @return true|WP_Error
	 */
	protected function do_activate( Feature $feature ) {
		$this->update_stored_state( $feature->get_slug(), true );

		return true;
	}

	/**
	 * Mark the theme as disabled in stored state.
	 *
	 * Theme files are never deleted. This only updates the stored state.
	 *
	 * @since 3.0.0
	 *
	 * @param Feature $feature Already type-guarded as Theme by the template.
	 *
	 * @return true|WP_Error
	 */
	protected function do_deactivate( Feature $feature ) {
		$this->update_stored_state( $feature->get_slug(), false );

		return true;
	}

	/**
	 * Verify theme ownership.
	 *
	 * @since 3.0.0
	 *
	 * @param Feature $feature Already type-guarded as Theme by the template.
	 *
	 * @return true|WP_Error
	 */
	protected function verify_ownership( Feature $feature ) {
		return $this->verify_theme_ownership( $feature );
	}

	/**
	 * @inheritDoc
	 */
	protected function get_not_found_after_install_error_code(): string {
		return Error_Code::THEME_NOT_FOUND_AFTER_INSTALL;
	}

	/**
	 * Reconcile live and stored state for themes.
	 *
	 * For themes, "live active" means installed on disk — not the same as
	 * "user enabled this feature". Unlike plugins where WP's active state is
	 * authoritative, a theme sitting on disk should not override an explicit
	 * user disable.
	 *
	 * Self-healing only goes downward: if a theme was removed from disk,
	 * stored state is corrected to false. A theme existing on disk never
	 * forces stored state back to true.
	 *
	 * A theme feature is active when it is on disk AND stored state says enabled.
	 *
	 * @since 3.0.0
	 *
	 * @param string $slug   The feature slug.
	 * @param bool   $live   The live active state from check_active().
	 * @param ?bool  $stored The stored state from wp_options (null if never set).
	 *
	 * @return bool The effective active state.
	 */
	protected function reconcile_state( string $slug, bool $live, ?bool $stored ): bool {
		// Theme removed from disk → self-heal stored state to inactive.
		if ( ! $live && $stored === true ) {
			$this->update_stored_state( $slug, false );

			if ( $this->is_wp_debug() ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log(
					sprintf(
						'[Uplink] Self-healed feature state for "%s": theme no longer on disk',
						$slug
					)
				);
			}
		}

		// Active = on disk AND stored state says enabled.
		// First check (null stored): default to not active — require explicit enable.
		return $live && ( $stored ?? false );
	}

	// ── Sync hooks ──────────────────────────────────────────────────────

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

	// ── Private helpers ─────────────────────────────────────────────────

	/**
	 * Install a theme via themes_api() and Theme_Upgrader.
	 *
	 * @since 3.0.0
	 *
	 * @param Feature $feature The feature whose theme to install.
	 *
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	private function install_theme( Feature $feature ) {
		/** @var Theme $feature */
		$theme_info = themes_api(
			'theme_information',
			[
				'slug'   => sanitize_key( $feature->get_slug() ),
				'fields' => [ 'sections' => false ],
			]
		);

		if ( is_wp_error( $theme_info ) ) {
			return new WP_Error(
				Error_Code::THEMES_API_FAILED,
				sprintf(
					/* translators: %1$s: feature name, %2$s: error message */
					__( 'Could not retrieve download information for "%1$s": %2$s', '%TEXTDOMAIN%' ),
					$feature->get_name(),
					$theme_info->get_error_message()
				)
			);
		}

		if ( empty( $theme_info->download_link ) ) {
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
		$upgrader = new Theme_Upgrader( $skin );

		$result = $upgrader->install(
			Cast::to_string( $theme_info->download_link )
		);

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
			$skin_errors = $skin->get_errors();

			$message = $skin_errors->has_errors()
				? $skin->get_error_messages()
				: __( 'An unknown error occurred during installation.', '%TEXTDOMAIN%' );

			return new WP_Error(
				Error_Code::INSTALL_FAILED,
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
	 * Verify that the installed theme belongs to an expected author.
	 *
	 * @since 3.0.0
	 *
	 * @param Feature $feature The feature whose theme to verify.
	 *
	 * @return true|WP_Error True if ownership matches or no theme on disk, WP_Error on mismatch.
	 */
	private function verify_theme_ownership( Feature $feature ) {
		/** @var Theme $feature */
		$expected_authors = $feature->get_authors();

		if ( $expected_authors === [] ) {
			return true;
		}

		$stylesheet = $feature->get_slug();
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
				/* translators: %1$s: theme stylesheet, %2$s: expected author(s), %3$s: actual author */
				__( 'The installed theme "%1$s" appears to belong to a different developer (expected "%2$s", found "%3$s") and cannot be managed as a feature.', '%TEXTDOMAIN%' ),
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
