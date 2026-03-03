<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Catalog;

use StellarWP\Uplink\Catalog\Results\Product_Catalog;
use StellarWP\Uplink\Utils\Collection;

/**
 * A collection of Product_Catalog objects, keyed by product slug.
 *
 * @since 3.0.0
 *
 * @extends Collection<Product_Catalog>
 */
final class Catalog_Collection extends Collection {

	/**
	 * Adds a product catalog to the collection.
	 *
	 * @since 3.0.0
	 *
	 * @param Product_Catalog $catalog Product catalog instance.
	 *
	 * @return Product_Catalog
	 */
	public function add( Product_Catalog $catalog ): Product_Catalog {
		if ( ! $this->offsetExists( $catalog->get_product_slug() ) ) {
			$this->offsetSet( $catalog->get_product_slug(), $catalog );
		}

		return $this->offsetGet( $catalog->get_product_slug() ) ?? $catalog;
	}

	/**
	 * Alias of offsetGet().
	 *
	 * @since 3.0.0
	 *
	 * @param string $offset The product slug.
	 *
	 * @return Product_Catalog|null
	 */
	public function get( $offset ): ?Product_Catalog { // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found -- Narrows return type for IDE support.
		return parent::get( $offset );
	}
}
