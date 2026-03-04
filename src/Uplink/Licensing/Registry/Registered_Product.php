<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Licensing\Registry;

use StellarWP\Uplink\Utils\License_Key;

/**
 * Represents a single product's entry in the unified product registry.
 *
 * Products report these via the stellarwp/uplink/product_registry filter
 * to opt in to the unified licensing system.
 *
 * @since 3.0.0
 */
final class Registered_Product {

	/**
	 * The product slug (e.g. 'give', 'kadence').
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public string $slug;

	/**
	 * The locally embedded license key, if the product ships with one.
	 *
	 * @since 3.0.0
	 *
	 * @var string|null
	 */
	public ?string $embedded_key;

	/**
	 * Human-readable product name.
	 *
	 * @since 3.0.0
	 *
	 * @var string|null
	 */
	public ?string $name;

	/**
	 * Currently installed version of the product.
	 *
	 * @since 3.0.0
	 *
	 * @var string|null
	 */
	public ?string $version;

	/**
	 * Product group or brand (e.g. 'givewp', 'kadence').
	 *
	 * @since 3.0.0
	 *
	 * @var string|null
	 */
	public ?string $group;

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 *
	 * @param string      $slug         The product slug.
	 * @param string|null $embedded_key The locally embedded license key.
	 * @param string|null $name         The human-readable product name.
	 * @param string|null $version      The currently installed version.
	 * @param string|null $group        The product group or brand.
	 */
	private function __construct(
		string $slug,
		?string $embedded_key,
		?string $name,
		?string $version,
		?string $group
	) {
		$this->slug         = $slug;
		$this->embedded_key = $embedded_key;
		$this->name         = $name;
		$this->version      = $version;
		$this->group        = $group;
	}

	/**
	 * Create a Registered_Product from a raw array.
	 *
	 * Returns null if the required 'slug' field is missing or empty.
	 *
	 * @since 3.0.0
	 *
	 * @param array<string, mixed> $data The product registration data.
	 *
	 * @return static|null
	 */
	public static function from_array( array $data ): ?self {
		if ( empty( $data['slug'] ) || ! is_string( $data['slug'] ) ) {
			return null;
		}

		$embedded_key = isset( $data['embedded_key'] ) && is_string( $data['embedded_key'] ) && License_Key::is_valid_format( $data['embedded_key'] )
			? $data['embedded_key']
			: null;

		return new self(
			$data['slug'],
			$embedded_key,
			isset( $data['name'] ) && is_string( $data['name'] ) ? $data['name'] : null,
			isset( $data['version'] ) && is_string( $data['version'] ) ? $data['version'] : null,
			isset( $data['group'] ) && is_string( $data['group'] ) ? $data['group'] : null,
		);
	}

	/**
	 * Whether this product has a locally embedded license key.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public function has_embedded_key(): bool {
		return $this->embedded_key !== null;
	}
}
