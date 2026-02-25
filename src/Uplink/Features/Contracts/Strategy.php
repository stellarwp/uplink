<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Contracts;

use StellarWP\Uplink\Features\Types\Feature;
use WP_Error;

/**
 * Strategy interface for enabling, disabling, and checking
 * the active state of a Feature.
 *
 * @since TBD
 */
interface Strategy {

	/**
	 * Enables a feature.
	 *
	 * @since TBD
	 *
	 * @param Feature $feature The feature to enable.
	 *
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public function enable( Feature $feature );

	/**
	 * Disables a feature.
	 *
	 * @since TBD
	 *
	 * @param Feature $feature The feature to disable.
	 *
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public function disable( Feature $feature );

	/**
	 * Checks whether a feature is currently active.
	 *
	 * Implementations should check live state rather than a cached flag.
	 * If the live state differs from any stored flag, the stored flag
	 * should be updated to match (self-healing).
	 *
	 * @since TBD
	 *
	 * @param Feature $feature The feature to check.
	 *
	 * @return bool Whether the feature is currently active.
	 */
	public function is_active( Feature $feature ): bool;
}
