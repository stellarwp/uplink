<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Catalog\Contracts;

use StellarWP\Uplink\Catalog\Catalog_Collection;
use WP_Error;

/**
 * Contract for the product catalog API client.
 *
 * @since 3.0.0
 */
interface Catalog_Client {

	/**
	 * Fetch the full catalog for all products.
	 *
	 * @since 3.0.0
	 *
	 * @return Catalog_Collection|WP_Error
	 */
	public function get_catalog();
}
