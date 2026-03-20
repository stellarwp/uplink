<?php declare( strict_types=1 );

namespace StellarWP\Uplink\API\REST\V1;

use StellarWP\Uplink\Licensing\Product_Collection;

/**
 * Builds the standard {key, products} response shape.
 *
 * @since 3.0.0
 */
final class License_Response {

	/**
	 * Builds the standard license response array.
	 *
	 * @since 3.0.0
	 *
	 * @param string|null        $key      The license key.
	 * @param Product_Collection $products The product collection.
	 *
	 * @return array{key: string|null, products: array<int, array<string, mixed>>}
	 */
	public static function make( ?string $key, Product_Collection $products ): array {
		return [
			'key'      => $key,
			'products' => $products->to_array(),
		];
	}
}
