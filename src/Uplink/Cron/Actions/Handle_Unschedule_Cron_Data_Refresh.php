<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Cron\Actions;

use StellarWP\Uplink\Catalog\Catalog_Repository;
use StellarWP\Uplink\Cron\ValueObjects\CronHook;

/**
 * Unschedules the data refresh cron event when no catalog plugins remain active.
 *
 * Reads the stored catalog from the DB (no API call) and cross-references its
 * plugin features against the active plugins list. If none match, the event is
 * removed. The cron will be rescheduled on the next page load via init if any
 * Uplink instance is still active.
 *
 * Conservative defaults: when the catalog is unreadable or contains no plugin
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
	 * @param Catalog_Repository $catalog The catalog repository
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
		if ( $this->has_active_catalog_plugin() ) {
			return;
		}

		$timestamp = wp_next_scheduled( CronHook::DATA_REFRESH );

		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, CronHook::DATA_REFRESH );
		}
	}

	/**
	 * Check whether any plugin listed in the stored catalog is still active.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	private function has_active_catalog_plugin(): bool {
		$catalog = $this->catalog->get();

		if ( is_wp_error( $catalog ) ) {
			return true;
		}

		$found_plugin_feature = false;

		foreach ( $catalog as $product ) {
			foreach ( $product->get_features() as $feature ) {
				$plugin_file = $feature->get_plugin_file();

				if ( $plugin_file === null ) {
					continue;
				}

				$found_plugin_feature = true;

				if ( is_plugin_active( $plugin_file ) ) {
					return true;
				}
			}
		}

		// If the catalog has no plugin features we cannot determine whether
		// Uplink is still needed, so leave the cron in place.
		if ( ! $found_plugin_feature ) {
			return true;
		}

		return false;
	}
}
