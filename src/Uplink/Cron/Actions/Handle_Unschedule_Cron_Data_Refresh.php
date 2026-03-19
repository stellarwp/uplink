<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Cron\Actions;

use StellarWP\Uplink\Catalog\Catalog_Repository;
use StellarWP\Uplink\Cron\ValueObjects\CronHook;
use StellarWP\Uplink\Features\Types\Feature;

use function get_stylesheet;
use function get_template;
use function is_plugin_active;
use function is_plugin_active_for_network;

/**
 * Unschedules the data refresh cron event when no catalog plugins or themes remain active.
 *
 * Reads the stored catalog from the DB (no API call) and cross-references its
 * plugin and theme features against the active plugins/theme. If none match, the
 * event is removed. The cron will be rescheduled on the next page load via init
 * if any Uplink instance is still active.
 *
 * Conservative defaults: when the catalog is unreadable or contains no installable
 * features, the event is left in place since we cannot confirm Uplink is gone.
 *
 * @since 3.0.0
 */
class Handle_Unschedule_Cron_Data_Refresh {

	/**
	 * @since 3.0.0
	 *
	 * @var Catalog_Repository
	 */
	private Catalog_Repository $catalog;

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 *
	 * @param Catalog_Repository $catalog The catalog repository.
	 */
	public function __construct( Catalog_Repository $catalog ) {
		$this->catalog = $catalog;
	}

	/**
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function __invoke(): void {
		if ( $this->has_active_catalog_feature() ) {
			return;
		}

		wp_clear_scheduled_hook( CronHook::DATA_REFRESH );
	}

	/**
	 * Check whether any plugin or theme listed in the stored catalog is still active.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	private function has_active_catalog_feature(): bool {
		$catalog = $this->catalog->get();

		if ( is_wp_error( $catalog ) ) {
			return true;
		}

		$found_catalog_feature = false;

		foreach ( $catalog as $product_catalog ) {
			foreach ( $product_catalog->get_features() as $catalog_feature ) {
				$type = $catalog_feature->get_type();

				if ( $type === Feature::TYPE_PLUGIN ) {
					$plugin_file = $catalog_feature->get_plugin_file();

					if ( $plugin_file === null ) {
						continue;
					}

					$found_catalog_feature = true;

					if ( is_plugin_active( $plugin_file ) || is_plugin_active_for_network( $plugin_file ) ) {
						return true;
					}
				} elseif ( $type === Feature::TYPE_THEME ) {
					$found_catalog_feature = true;
					$slug                  = $catalog_feature->get_feature_slug();

					if ( get_stylesheet() === $slug || get_template() === $slug ) {
						return true;
					}
				}
			}
		}

		// If the catalog has no installable features we cannot determine whether
		// Uplink is still needed, so leave the cron in place.
		if ( ! $found_catalog_feature ) {
			return true;
		}

		return false;
	}
}
