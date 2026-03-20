<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Licensing\Registry;

/**
 * Collects and normalizes products that have opted in to unified licensing.
 *
 * Products register via the stellarwp/uplink/product_registry filter by
 * appending a data array for each product they represent:
 *
 *   add_filter( 'stellarwp/uplink/product_registry', function( array $products ) {
 *       $products[] = [
 *           'slug'         => 'give',
 *           'embedded_key' => GIVE_LICENSE_KEY,
 *           'name'         => 'GiveWP',
 *           'version'      => GIVE_VERSION,
 *           'product'      => 'givewp',
 *       ];
 *       return $products;
 *   } );
 *
 * @since 3.0.0
 */
final class Product_Registry {

	/**
	 * Filter hook used by products to opt in to unified licensing.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const FILTER = 'stellarwp/uplink/product_registry';

	/**
	 * Get all products that have opted in to unified licensing.
	 *
	 * Entries that are not arrays or that are missing a valid slug are silently
	 * skipped, so a poorly formed callback cannot break the rest of the registry.
	 *
	 * @since 3.0.0
	 *
	 * @return Registered_Product[]
	 */
	public function all(): array {
		$raw = (array) apply_filters( self::FILTER, [] );

		$products = [];

		foreach ( $raw as $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}

			/** @var array<string, mixed> $entry */
			$product = Registered_Product::from_array( $entry );

			if ( $product !== null ) {
				$products[] = $product;
			}
		}

		return $products;
	}

	/**
	 * Find the first registered product that has a locally embedded license key.
	 *
	 * @since 3.0.0
	 *
	 * @return Registered_Product|null
	 */
	public function first_with_embedded_key(): ?Registered_Product {
		foreach ( $this->all() as $product ) {
			if ( $product->has_embedded_key() ) {
				return $product;
			}
		}

		return null;
	}
}
