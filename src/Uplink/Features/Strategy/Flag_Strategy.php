<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Strategy;

use StellarWP\Uplink\Features\Error_Code;
use WP_Error;

/**
 * Flag Strategy — toggles features via a wp_options flag.
 *
 * This is the simplest strategy: enable/disable just sets a boolean option
 * in the database. The stored DB flag is the sole source of truth.
 *
 * Option key: `stellarwp_uplink_feature_{slug}_active`
 * Values: '1' (active) or '0' (inactive).
 *
 * @since 3.0.0
 */
class Flag_Strategy extends Abstract_Strategy {

	/**
	 * Build the wp_options key for the feature's stored state.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	private function get_option_key(): string {
		return 'stellarwp_uplink_feature_' . $this->feature->get_slug() . '_active';
	}

	/**
	 * Enable the Flag feature by setting its DB flag to active.
	 *
	 * Idempotent: returns true if already active.
	 *
	 * @since 3.0.0
	 *
	 * @return true
	 */
	public function enable() {
		update_option( $this->get_option_key(), '1', true );

		return true;
	}

	/**
	 * Flag features do not support updates.
	 *
	 * @since 3.0.0
	 *
	 * @return WP_Error Always returns an error.
	 */
	public function update() {
		return new WP_Error(
			Error_Code::UPDATE_NOT_SUPPORTED,
			sprintf(
				/* translators: %s: feature name */
				__( 'The feature "%s" does not support updates.', '%TEXTDOMAIN%' ),
				$this->feature->get_name()
			)
		);
	}

	/**
	 * Disable the Flag feature by setting its DB flag to inactive.
	 *
	 * Idempotent: returns true if already inactive.
	 *
	 * @since 3.0.0
	 *
	 * @return true
	 */
	public function disable() {
		update_option( $this->get_option_key(), '0', true );

		return true;
	}

	/**
	 * Check whether the Flag feature is currently active.
	 *
	 * The stored DB flag is the source of truth. Defaults to false if no
	 * state has been stored yet.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public function is_active(): bool {
		$raw = get_option( $this->get_option_key(), null );

		return $raw !== null && (bool) $raw;
	}
}
