<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Licensing;

use StellarWP\Uplink\Licensing\Results\Product_Entry;
use StellarWP\Uplink\Utils\Collection;

/**
 * A collection of Product_Entry objects, keyed by product slug.
 *
 * @since 3.0.0
 *
 * @extends Collection<Product_Entry>
 */
class Product_Collection extends Collection {

	/**
	 * Adds a product entry to the collection, keyed by its slug.
	 *
	 * @since 3.0.0
	 *
	 * @param Product_Entry $entry Product entry instance.
	 *
	 * @return Product_Entry
	 */
	public function add( Product_Entry $entry ): Product_Entry {
		if ( ! $this->offsetExists( $entry->get_product_slug() ) ) {
			$this->offsetSet( $entry->get_product_slug(), $entry );
		}

		return $this->offsetGet( $entry->get_product_slug() ) ?? $entry;
	}

	/**
	 * Retrieves a product entry by slug.
	 *
	 * @since 3.0.0
	 *
	 * @param string $offset The product slug.
	 *
	 * @return Product_Entry|null
	 */
	public function get( $offset ): ?Product_Entry { // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found -- Narrows return type for IDE support.
		return parent::get( $offset );
	}

	/**
	 * Creates a Product_Collection from an array of Product_Entry objects.
	 *
	 * @since 3.0.0
	 *
	 * @param Product_Entry[] $entries Product entries.
	 *
	 * @return self
	 */
	public static function from_array( array $entries ): self {
		$collection = new self();

		foreach ( $entries as $entry ) {
			$collection->add( $entry );
		}

		return $collection;
	}
}
