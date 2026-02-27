<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Strategy;

use StellarWP\Uplink\Features\Types\Built_In;
use StellarWP\Uplink\Features\Types\Feature;
use WP_Error;

/**
 * Built-In Strategy — toggles features via a wp_options flag.
 *
 * This is the simplest strategy: enable/disable just sets a boolean option
 * in the database. The stored state IS the source of truth (unlike Zip_Strategy
 * where the live WordPress plugin state is authoritative and stored state is a
 * self-healing cache).
 *
 * Option key: `stellarwp_uplink_feature_{slug}_active`
 * Values: '1' (active) or '0' (inactive).
 *
 * @since 3.0.0
 */
class Built_In_Strategy extends Abstract_Strategy {

	/**
	 * Enable a Built-In feature by setting its DB flag to active.
	 *
	 * Idempotent: returns true if already active.
	 *
	 * @since 3.0.0
	 *
	 * @param Feature $feature Must be a Built_In instance.
	 *
	 * @return true|WP_Error True on success, WP_Error on type mismatch.
	 */
	public function enable( Feature $feature ) {
		if ( ! $feature instanceof Built_In ) {
			return new WP_Error(
				'feature_type_mismatch',
				'Built_In_Strategy can only enable Built_In instances.'
			);
		}

		$this->update_stored_state( $feature->get_slug(), true );

		return true;
	}

	/**
	 * Disable a Built-In feature by setting its DB flag to inactive.
	 *
	 * Idempotent: returns true if already inactive.
	 *
	 * @since 3.0.0
	 *
	 * @param Feature $feature Must be a Built_In instance.
	 *
	 * @return true|WP_Error True on success, WP_Error on type mismatch.
	 */
	public function disable( Feature $feature ) {
		if ( ! $feature instanceof Built_In ) {
			return new WP_Error(
				'feature_type_mismatch',
				'Built_In_Strategy can only disable Built_In instances.'
			);
		}

		$this->update_stored_state( $feature->get_slug(), false );

		return true;
	}

	/**
	 * Check whether a Built-In feature is currently active.
	 *
	 * The stored DB flag is the source of truth. Defaults to false if no
	 * state has been stored yet.
	 *
	 * @since 3.0.0
	 *
	 * @param Feature $feature Must be a Built_In instance.
	 *
	 * @return bool
	 */
	public function is_active( Feature $feature ): bool {
		if ( ! $feature instanceof Built_In ) {
			return false;
		}

		return $this->get_stored_state( $feature->get_slug() ) === true;
	}
}
