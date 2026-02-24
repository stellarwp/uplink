<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Contracts;

use StellarWP\Uplink\Features\Feature;
use WP_Error;

/**
 * Contract for feature-gating strategies.
 *
 * Each strategy defines how a feature is enabled, disabled, and queried for
 * its active state. The Zip strategy installs/activates WordPress plugins;
 * future strategies might toggle config flags, enable modules, etc.
 *
 * Return type for enable()/disable() is intentionally omitted from the PHP
 * signature because PHP 7.1 cannot express `true|WP_Error`. The docblock
 * is the source of truth.
 *
 * @since 3.0.0
 */
interface Strategy {

	/**
	 * Enable the given feature.
	 *
	 * Implementations should be idempotent: calling enable() on an already-
	 * active feature returns true without side effects.
	 *
	 * @since 3.0.0
	 *
	 * @param Feature $feature The feature to enable.
	 *
	 * @return true|WP_Error True on success, WP_Error with a specific error
	 *                       code on failure.
	 */
	public function enable( Feature $feature );

	/**
	 * Disable the given feature.
	 *
	 * Implementations should be idempotent: calling disable() on an already-
	 * inactive feature returns true without side effects.
	 *
	 * @since 3.0.0
	 *
	 * @param Feature $feature The feature to disable.
	 *
	 * @return true|WP_Error True on success, WP_Error with a specific error
	 *                       code on failure.
	 */
	public function disable( Feature $feature );

	/**
	 * Check whether the given feature is currently active.
	 *
	 * @since 3.0.0
	 *
	 * @param Feature $feature The feature to check.
	 *
	 * @return bool
	 */
	public function is_active( Feature $feature ): bool;

}
