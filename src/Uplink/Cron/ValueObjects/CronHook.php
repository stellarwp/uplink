<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Cron\ValueObjects;

/**
 * Cron event hook name constants.
 *
 * @since 3.0.0
 */
class CronHook {

	/**
	 * Hook for the 12-hour license and catalog data refresh.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	const DATA_REFRESH = 'stellarwp_uplink_data_refresh';
}
