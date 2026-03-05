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
final class Product_Collection extends Collection {

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
	 * Converts the collection to an array of raw data arrays.
	 *
	 * @since 3.0.0
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function to_array(): array {
		$data = [];

		foreach ( $this as $entry ) {
			$data[] = $entry->to_array();
		}

		return $data;
	}

	/**
	 * Creates a Product_Collection from an array of Product_Entry objects or raw data arrays.
	 *
	 * @since 3.0.0
	 *
	 * @param array<int, Product_Entry|array<string, mixed>> $entries Product entries or raw arrays.
	 *
	 * @return self
	 */
	public static function from_array( array $entries ): self {
		$collection = new self();

		foreach ( $entries as $entry ) {
			if ( $entry instanceof Product_Entry ) {
				$collection->add( $entry );
			} elseif ( is_array( $entry ) ) {
				$collection->add( Product_Entry::from_array( $entry ) );
			}
		}

		return $collection;
	}
}
