<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Sync;

use StellarWP\Uplink\Features\Manager;
use StellarWP\Uplink\Features\Types\Plugin;

/**
 * Syncs feature stored state when plugins are activated or deactivated
 * through WordPress (e.g. the Plugins admin page, WP-CLI, etc.).
 *
 * Without this, the stored feature state would drift from reality
 * until the next is_active() call self-heals it.
 *
 * @since 3.0.0
 */
class Plugin_Activation_Sync {

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
	 * Sync stored state when a plugin is activated via WordPress.
	 *
	 * @since 3.0.0
	 *
	 * @param string $plugin       Plugin file path relative to plugins directory.
	 * @param bool   $network_wide Whether the activation was network-wide.
	 *
	 * @return void
	 */
	public function on_activated( string $plugin, bool $network_wide ): void {
		$feature = $this->find( $plugin );

		if ( $feature !== null ) {
			$feature->mark_active();
		}
	}

	/**
	 * Sync stored state when a plugin is deactivated via WordPress.
	 *
	 * @since 3.0.0
	 *
	 * @param string $plugin       Plugin file path relative to plugins directory.
	 * @param bool   $network_wide Whether the deactivation was network-wide.
	 *
	 * @return void
	 */
	public function on_deactivated( string $plugin, bool $network_wide ): void {
		$feature = $this->find( $plugin );

		if ( $feature !== null ) {
			$feature->mark_inactive();
		}
	}

	/**
	 * Find a Plugin feature by its plugin file path.
	 *
	 * @since 3.0.0
	 *
	 * @param string $plugin_file Plugin file path relative to plugins directory.
	 *
	 * @return Plugin|null
	 */
	private function find( string $plugin_file ): ?Plugin {
		$features = $this->manager->get_features();

		if ( is_wp_error( $features ) ) {
			return null;
		}

		foreach ( $features->filter( null, null, null, 'plugin' ) as $feature ) {
			if (
				$feature instanceof Plugin
				&& $feature->get_plugin_file() === $plugin_file
			) {
				return $feature;
			}
		}

		return null;
	}
}
