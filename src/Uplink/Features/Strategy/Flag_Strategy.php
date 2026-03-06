<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Strategy;

/**
 * Flag Strategy — toggles features via a wp_options flag.
 *
 * This is the simplest strategy: enable/disable just sets a boolean option
 * in the database. The stored state IS the source of truth (unlike Plugin_Strategy
 * where the live WordPress plugin state is authoritative and stored state is a
 * self-healing cache).
 *
 * Option key: `stellarwp_uplink_feature_{slug}_active`
 * Values: '1' (active) or '0' (inactive).
 *
 * @since 3.0.0
 */
class Flag_Strategy extends Abstract_Strategy {

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
		$this->update_stored_state( $this->feature->get_slug(), true );

		return true;
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
		$this->update_stored_state( $this->feature->get_slug(), false );

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
		return $this->get_stored_state( $this->feature->get_slug() ) === true;
	}
}
