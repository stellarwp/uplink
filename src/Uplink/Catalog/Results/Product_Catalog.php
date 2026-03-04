<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Catalog\Results;

use StellarWP\Uplink\Utils\Cast;

/**
 * A single product's catalog of tiers and features.
 *
 * Immutable value object hydrated from the catalog API response.
 *
 * @since 3.0.0
 */
final class Product_Catalog {

	/**
	 * The product slug.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	protected string $product_slug;

	/**
	 * The tier collection, sorted by rank.
	 *
	 * @since 3.0.0
	 *
	 * @var Tier_Collection
	 */
	protected Tier_Collection $tiers;

	/**
	 * The feature objects.
	 *
	 * @since 3.0.0
	 *
	 * @var Catalog_Feature[]
	 */
	protected array $features;

	/**
	 * Constructor for a Product_Catalog.
	 *
	 * @since 3.0.0
	 *
	 * @param string            $product_slug The product slug.
	 * @param Tier_Collection   $tiers        The tier collection.
	 * @param Catalog_Feature[] $features     The feature objects.
	 *
	 * @return void
	 */
	public function __construct( string $product_slug, Tier_Collection $tiers, array $features ) {
		$this->product_slug = $product_slug;
		$this->tiers        = $tiers;
		$this->features     = $features;
	}

	/**
	 * Creates a Product_Catalog from a raw data array.
	 *
	 * @since 3.0.0
	 *
	 * @param array<string, mixed> $data The product catalog data.
	 *
	 * @return self
	 */
	public static function from_array( array $data ): self {
		$tiers = new Tier_Collection();

		if ( isset( $data['tiers'] ) && is_array( $data['tiers'] ) ) {
			$tier_objects = [];

			foreach ( $data['tiers'] as $tier_data ) {
				if ( is_array( $tier_data ) ) {
					/** @var array<string, mixed> $tier_data */
					$tier_objects[] = Catalog_Tier::from_array( $tier_data );
				}
			}

			usort(
				$tier_objects,
				static function ( Catalog_Tier $a, Catalog_Tier $b ): int {
					return $a->get_rank() <=> $b->get_rank();
				}
			);

			foreach ( $tier_objects as $tier ) {
				$tiers->add( $tier );
			}
		}

		$features = [];

		if ( isset( $data['features'] ) && is_array( $data['features'] ) ) {
			foreach ( $data['features'] as $feature_data ) {
				if ( is_array( $feature_data ) ) {
					/** @var array<string, mixed> $feature_data */
					$features[] = Catalog_Feature::from_array( $feature_data );
				}
			}
		}

		return new self(
			Cast::to_string( $data['product_slug'] ?? '' ),
			$tiers,
			$features,
		);
	}

	/**
	 * Converts the product catalog to an associative array.
	 *
	 * @since 3.0.0
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return [
			'product_slug' => $this->product_slug,
			'tiers'        => array_values(
				array_map(
					static function ( Catalog_Tier $tier ): array {
						return $tier->to_array();
					},
					iterator_to_array( $this->tiers )
				)
			),
			'features'     => array_map(
				static function ( Catalog_Feature $feature ): array {
					return $feature->to_array();
				},
				$this->features
			),
		];
	}

	/**
	 * Gets the product slug.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_product_slug(): string {
		return $this->product_slug;
	}

	/**
	 * Gets the tier collection, ordered by rank.
	 *
	 * @since 3.0.0
	 *
	 * @return Tier_Collection
	 */
	public function get_tiers(): Tier_Collection {
		return $this->tiers;
	}

	/**
	 * Gets a tier by its slug, or null if not found.
	 *
	 * @since 3.0.0
	 *
	 * @param string $slug The tier slug.
	 *
	 * @return Catalog_Tier|null
	 */
	public function get_tier_by_slug( string $slug ): ?Catalog_Tier {
		return $this->tiers->get( $slug );
	}

	/**
	 * Gets the hydrated feature objects.
	 *
	 * @since 3.0.0
	 *
	 * @return Catalog_Feature[]
	 */
	public function get_features(): array {
		return $this->features;
	}
}
