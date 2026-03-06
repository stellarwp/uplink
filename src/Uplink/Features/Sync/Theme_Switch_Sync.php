<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Sync;

use StellarWP\Uplink\Features\Manager;
use StellarWP\Uplink\Features\Types\Theme;
use WP_Theme;

/**
 * Syncs feature stored state when the active theme changes through
 * WordPress (e.g. the Appearance > Themes UI, WP-CLI, etc.).
 *
 * @since 3.0.0
 */
class Theme_Switch_Sync {

	/**
	 * @since 3.0.0
	 *
	 * @var Manager
	 */
	private Manager $manager;

	/**
	 * @since 3.0.0
	 *
	 * @param Manager $manager The feature manager.
	 */
	public function __construct( Manager $manager ) {
		$this->manager = $manager;
	}

	/**
	 * Sync stored state when the active theme changes.
	 *
	 * If the old theme corresponds to a theme feature, mark it inactive.
	 * If the new theme corresponds to a theme feature, mark it active.
	 *
	 * @since 3.0.0
	 *
	 * @param string   $new_name  Name of the new theme.
	 * @param WP_Theme $new_theme The new theme object.
	 * @param WP_Theme $old_theme The old theme object.
	 *
	 * @return void
	 */
	public function on_switch( string $new_name, WP_Theme $new_theme, WP_Theme $old_theme ): void {
		$features = $this->manager->get_features();

		if ( is_wp_error( $features ) ) {
			return;
		}

		$old_stylesheet = $old_theme->get_stylesheet();
		$new_stylesheet = $new_theme->get_stylesheet();

		foreach ( $features->filter( null, null, null, 'theme' ) as $feature ) {
			if ( ! $feature instanceof Theme ) {
				continue;
			}

			if ( $feature->get_slug() === $old_stylesheet ) {
				$feature->mark_inactive();
			}

			if ( $feature->get_slug() === $new_stylesheet ) {
				$feature->mark_active();
			}
		}
	}
}
