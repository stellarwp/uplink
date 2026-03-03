<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Catalog\Results;

use StellarWP\Uplink\Utils\Cast;

/**
 * A single tier definition from the product catalog.
 *
 * Immutable value object hydrated from the catalog API response.
 *
 * @since 3.0.0
 *
 * @phpstan-type TierAttributes array{
 *     slug: string,
 *     name: string,
 *     rank: int,
 *     purchase_url: string,
 * }
 */
class Catalog_Tier {

	/**
	 * The tier attributes.
	 *
	 * @since 3.0.0
	 *
	 * @var TierAttributes
	 */
	protected array $attributes = [
		'slug'         => '',
		'name'         => '',
		'rank'         => 0,
		'purchase_url' => '',
	];

	/**
	 * Constructor for a Catalog_Tier.
	 *
	 * @since 3.0.0
	 *
	 * @phpstan-param TierAttributes $attributes
	 *
	 * @param array $attributes The tier attributes.
	 *
	 * @return void
	 */
	public function __construct( array $attributes ) {
		$this->attributes = $attributes;
	}

	/**
	 * Creates a Catalog_Tier from a raw data array.
	 *
	 * @since 3.0.0
	 *
	 * @param array<string, mixed> $data The tier data.
	 *
	 * @return self
	 */
	public static function from_array( array $data ): self {
		return new self(
			[
				'slug'         => Cast::to_string( $data['slug'] ?? '' ),
				'name'         => Cast::to_string( $data['name'] ?? '' ),
				'rank'         => Cast::to_int( $data['rank'] ?? 0 ),
				'purchase_url' => Cast::to_string( $data['purchase_url'] ?? '' ),
			]
		);
	}

	/**
	 * Converts the tier to an associative array.
	 *
	 * @since 3.0.0
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return $this->attributes;
	}

	/**
	 * Gets the tier slug.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return $this->attributes['slug'];
	}

	/**
	 * Gets the tier display name.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->attributes['name'];
	}

	/**
	 * Gets the tier rank for ordering and comparison.
	 *
	 * @since 3.0.0
	 *
	 * @return int
	 */
	public function get_rank(): int {
		return $this->attributes['rank'];
	}

	/**
	 * Gets the purchase URL for this tier.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_purchase_url(): string {
		return $this->attributes['purchase_url'];
	}
}
