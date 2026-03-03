<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Contracts;

use StellarWP\Uplink\Features\Feature_Collection;
use WP_Error;

/**
 * Contract for the feature catalog API client.
 *
 * @since 3.0.0
 */
interface Feature_Client {

	/**
	 * Fetch the full feature catalog.
	 *
	 * @since 3.0.0
	 *
	 * @return Feature_Collection|WP_Error
	 */
	public function get_features();
}
