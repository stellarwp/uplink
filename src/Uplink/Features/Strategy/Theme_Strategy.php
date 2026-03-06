<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Strategy;

use StellarWP\Uplink\Features\Error_Code;
use StellarWP\Uplink\Features\Types\Feature;
use StellarWP\Uplink\Features\Types\Theme;
use StellarWP\Uplink\Utils\Cast;
use WP_Error;
use WP_Ajax_Upgrader_Skin;
use Theme_Upgrader;

use function sanitize_key;
use function themes_api;
use function wp_get_theme;

/**
 * Theme Strategy — installs WordPress themes as "features".
 *
 * The shared enable/disable/is_active/ensure_installed flow is templated by
 * Installable_Strategy. This class provides the WP-specific hooks:
 * - do_install()     → themes_api() + Theme_Upgrader
 * - do_activate()    → no-op (theme is already installed)
 * - do_deactivate()  → no-op (theme files are never deleted)
 * - verify_ownership → Author header check via wp_get_theme()
 *
 * A theme feature is active when the theme is installed on disk.
 * No DB option is stored — disk presence is the sole source of truth.
 * A theme feature is disabled if the theme is uninstalled (deleted from disk).
 *
 * @since 3.0.0
 */
class Theme_Strategy extends Installable_Strategy {

	/**
	 * @var Theme
	 */
	protected Feature $feature;

	/**
	 * Construct the strategy with a Theme feature.
	 *
	 * Narrows the parent's Feature type to Theme.
	 *
	 * @since 3.0.0
	 *
	 * @param Theme $feature The theme feature this strategy operates on.
	 *
	 * phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found -- Narrows parameter type from Feature to Theme.
	 */
	public function __construct( Theme $feature ) {
		parent::__construct( $feature );
	}

	// ── Abstract method implementations ─────────────────────────────────

	/**
	 * @inheritDoc
	 */
	protected function get_type_mismatch_message(): string {
		return __( 'This feature type is not supported by the Theme installer.', '%TEXTDOMAIN%' );
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
	 * @return bool
	 */
	protected function check_active(): bool {
		return $this->check_installed();
	}

	/**
	 * Check whether the theme is installed on disk.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	protected function check_installed(): bool {
		return wp_get_theme( $this->feature->get_slug() )->exists();
	}

	/**
	 * Install the theme via themes_api() and Theme_Upgrader.
	 *
	 * @since 3.0.0
	 *
	 * @return true|WP_Error
	 */
	protected function do_install() {
		return $this->install_theme();
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
	 * @return true|WP_Error
	 */
	protected function do_activate() {
		return true;
	}

	/**
	 * Mark the theme as disabled in stored state.
	 *
	 * Theme files are never deleted. This only updates the stored state.
	 *
	 * @since 3.0.0
	 *
	 * @return true|WP_Error
	 */
	protected function do_deactivate() {
		return true;
	}

	/**
	 * Verify theme ownership.
	 *
	 * @since 3.0.0
	 *
	 * @return true|WP_Error
	 */
	protected function verify_ownership() {
		return $this->verify_theme_ownership();
	}

	/**
	 * @inheritDoc
	 */
	protected function get_not_found_after_install_error_code(): string {
		return Error_Code::THEME_NOT_FOUND_AFTER_INSTALL;
	}

	// ── Private helpers ─────────────────────────────────────────────────

	/**
	 * Install a theme via themes_api() and Theme_Upgrader.
	 *
	 * @since 3.0.0
	 *
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	private function install_theme() {
		$theme_info = themes_api(
			'theme_information',
			[
				'slug'   => sanitize_key( $this->feature->get_slug() ),
				'fields' => [ 'sections' => false ],
			]
		);

		if ( is_wp_error( $theme_info ) ) {
			return new WP_Error(
				Error_Code::THEMES_API_FAILED,
				sprintf(
					/* translators: %1$s: feature name, %2$s: error message */
					__( 'Could not retrieve download information for "%1$s": %2$s', '%TEXTDOMAIN%' ),
					$this->feature->get_name(),
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
					$this->feature->get_name()
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
					$this->feature->get_name(),
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
					$this->feature->get_name(),
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
	 * @return true|WP_Error True if ownership matches or no theme on disk, WP_Error on mismatch.
	 */
	private function verify_theme_ownership() {
		$expected_authors = $this->feature->get_authors();

		if ( $expected_authors === [] ) {
			return true;
		}

		$stylesheet = $this->feature->get_slug();
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
