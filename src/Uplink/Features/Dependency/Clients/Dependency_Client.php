<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Dependency\Clients;

use StellarWP\Uplink\Features\Dependency\Dependency_Collection;
use WP_Error;

/**
 * Contract for the feature dependency API client.
 *
 * @since 3.0.0
 */
interface Dependency_Client {

	/**
	 * Fetch the full dependency graph for all features.
	 *
	 * @since 3.0.0
	 *
	 * @return Dependency_Collection|WP_Error
	 */
	public function get_dependencies();
}
