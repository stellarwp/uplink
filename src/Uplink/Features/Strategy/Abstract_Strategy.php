<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Strategy;

use StellarWP\Uplink\Features\Contracts\Strategy;
use StellarWP\Uplink\Traits\With_Debugging;

/**
 * Base class for feature-gating strategies with shared stored-state logic.
 *
 * All strategies persist an "active" flag in wp_options using a common naming
 * convention: `stellarwp_uplink_feature_{slug}_active`. This class provides
 * the option key construction and read/write helpers so concrete strategies
 * don't duplicate the boilerplate.
 *
 * @since 3.0.0
 */
abstract class Abstract_Strategy implements Strategy {

	use With_Debugging;

	/**
	 * Option key prefix for stored feature state.
	 *
	 * Full key: stellarwp_uplink_feature_{slug}_active
	 * Value: '1' (active) or '0' (inactive).
	 *
	 * @since 3.0.0
	 */
	protected const OPTION_PREFIX = 'stellarwp_uplink_feature_';

	/**
	 * Option key suffix for stored feature state.
	 *
	 * @since 3.0.0
	 */
	protected const OPTION_SUFFIX = '_active';

	/**
	 * Build the wp_options key for a feature's stored state.
	 *
	 * @since 3.0.0
	 *
	 * @param string $slug Feature slug.
	 *
	 * @return string
	 */
	protected function get_option_key( string $slug ): string {
		return self::OPTION_PREFIX . $slug . self::OPTION_SUFFIX;
	}

	/**
	 * Update the stored feature state in wp_options.
	 *
	 * Uses autoload=true because feature state is read on every page load
	 * (via is_active) and should be in the autoloaded option cache.
	 *
	 * @since 3.0.0
	 *
	 * @param string $slug   Feature slug.
	 * @param bool   $active Whether the feature is active.
	 *
	 * @return void
	 */
	protected function update_stored_state( string $slug, bool $active ): void {
		update_option(
			$this->get_option_key( $slug ),
			$active ? '1' : '0',
			true
		);
	}

	/**
	 * Read the stored feature state from wp_options.
	 *
	 * @since 3.0.0
	 *
	 * @param string $slug Feature slug.
	 *
	 * @return bool|null True if stored as active, false if stored as inactive,
	 *                   null if no stored state exists yet.
	 */
	protected function get_stored_state( string $slug ): ?bool {
		$raw = get_option(
			$this->get_option_key( $slug ),
			null
		);

		if ( $raw === null ) {
			return null;
		}

		return (bool) $raw;
	}

}
