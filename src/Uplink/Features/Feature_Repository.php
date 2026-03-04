<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features;

use StellarWP\Uplink\Catalog\Catalog_Repository;
use StellarWP\Uplink\Catalog\Results\Catalog_Feature;
use StellarWP\Uplink\Catalog\Results\Product_Catalog;
use StellarWP\Uplink\Features\Types\Feature;
use StellarWP\Uplink\Licensing\Product_Collection;
use StellarWP\Uplink\Licensing\Product_Repository;
use WP_Error;

/**
 * Joins catalog and licensing data to produce a resolved Feature_Collection.
 *
 * For each catalog feature, the repository computes is_available by comparing
 * the site's licensed tier rank against the feature's minimum tier rank.
 *
 * @since 3.0.0
 */
class Feature_Repository {

	/**
	 * Transient key for the cached feature catalog.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const TRANSIENT_KEY = 'stellarwp_uplink_feature_catalog';

	/**
	 * Default cache duration in seconds (12 hours).
	 *
	 * @since 3.0.0
	 *
	 * @var int
	 */
	private const CACHE_DURATION = HOUR_IN_SECONDS * 12;

	/**
	 * Map of catalog type strings to Feature subclass names.
	 *
	 * @since 3.0.0
	 *
	 * @var array<string, class-string<Feature>>
	 */
	private array $type_map = [];

	/**
	 * The catalog repository.
	 *
	 * @since 3.0.0
	 *
	 * @var Catalog_Repository
	 */
	private Catalog_Repository $catalog;

	/**
	 * The licensing product repository.
	 *
	 * @since 3.0.0
	 *
	 * @var Product_Repository
	 */
	private Product_Repository $licensing;

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 *
	 * @param Catalog_Repository $catalog   The catalog repository.
	 * @param Product_Repository $licensing  The licensing product repository.
	 */
	public function __construct( Catalog_Repository $catalog, Product_Repository $licensing ) {
		$this->catalog   = $catalog;
		$this->licensing = $licensing;
	}

	/**
	 * Registers a Feature subclass for a given catalog type string.
	 *
	 * @since 3.0.0
	 *
	 * @param string                $type          The catalog type identifier (e.g. 'plugin', 'flag', 'theme').
	 * @param class-string<Feature> $feature_class The Feature subclass FQCN.
	 *
	 * @return void
	 */
	public function register_type( string $type, string $feature_class ): void { // phpcs:ignore Squiz.Commenting.FunctionComment.IncorrectTypeHint -- class-string<Feature> is a PHPStan type narrowing.
		$this->type_map[ $type ] = $feature_class;
	}

	/**
	 * Gets the resolved feature collection, using the transient cache when available.
	 *
	 * Joins catalog features with licensing data to compute is_available
	 * based on the site's licensed tier rank.
	 *
	 * @since 3.0.0
	 *
	 * @param string $key    License key.
	 * @param string $domain Site domain.
	 *
	 * @return Feature_Collection|WP_Error
	 */
	public function get( string $key, string $domain ) {
		$cached = get_transient( self::TRANSIENT_KEY );

		if ( is_wp_error( $cached ) ) {
			return $cached;
		}

		if ( $cached instanceof Feature_Collection ) {
			return $cached;
		}

		return $this->fetch( $key, $domain );
	}

	/**
	 * Deletes the transient cache and re-fetches.
	 *
	 * @since 3.0.0
	 *
	 * @param string $key    License key.
	 * @param string $domain Site domain.
	 *
	 * @return Feature_Collection|WP_Error
	 */
	public function refresh( string $key, string $domain ) {
		delete_transient( self::TRANSIENT_KEY );

		return $this->fetch( $key, $domain );
	}

	/**
	 * Fetches catalog and licensing data, joins them, and caches the result.
	 *
	 * @since 3.0.0
	 *
	 * @param string $key    License key.
	 * @param string $domain Site domain.
	 *
	 * @return Feature_Collection|WP_Error
	 */
	protected function fetch( string $key, string $domain ) {
		$catalog = $this->catalog->get();

		if ( is_wp_error( $catalog ) ) {
			set_transient( self::TRANSIENT_KEY, $catalog, self::CACHE_DURATION );

			return $catalog;
		}

		$products = $this->licensing->get( $key, $domain );

		$collection = new Feature_Collection();

		foreach ( $catalog as $product ) {
			if ( ! $product instanceof Product_Catalog ) {
				continue;
			}

			$license_tier_rank = $this->resolve_license_tier_rank( $product, $products );

			foreach ( $product->get_features() as $catalog_feature ) {
				$feature = $this->hydrate_feature( $catalog_feature, $product, $license_tier_rank );

				if ( $feature !== null ) {
					$collection->add( $feature );
				}
			}
		}

		set_transient( self::TRANSIENT_KEY, $collection, self::CACHE_DURATION );

		return $collection;
	}

	/**
	 * Resolves the licensed tier rank for a given product.
	 *
	 * Constructs the catalog tier slug as {product_slug}-{license_tier}
	 * and looks up its rank in the product's tier collection.
	 *
	 * @since 3.0.0
	 *
	 * @param Product_Catalog             $product  The catalog product.
	 * @param Product_Collection|WP_Error $products The licensing product collection, or WP_Error if unavailable.
	 *
	 * @return int The tier rank, or 0 if the product has no license.
	 */
	private function resolve_license_tier_rank( Product_Catalog $product, $products ): int {
		if ( ! $products instanceof Product_Collection ) {
			return 0;
		}

		$license = $products->get( $product->get_product_slug() );

		if ( $license === null ) {
			return 0;
		}

		$tier_slug = $product->get_product_slug() . '-' . $license->get_tier();
		$tier      = $product->get_tier_by_slug( $tier_slug );

		return $tier !== null ? $tier->get_rank() : 0;
	}

	/**
	 * Hydrates a Feature object from a catalog feature entry.
	 *
	 * Maps catalog types (plugin, flag, theme) to Feature subclasses
	 * (Zip, Built_In) and computes is_available from tier rank comparison.
	 *
	 * @since 3.0.0
	 *
	 * @param Catalog_Feature $catalog_feature   The catalog feature entry.
	 * @param Product_Catalog $product           The parent catalog product.
	 * @param int             $license_tier_rank The resolved license tier rank.
	 *
	 * @return Feature|null The hydrated feature, or null for unknown types.
	 */
	private function hydrate_feature(
		Catalog_Feature $catalog_feature,
		Product_Catalog $product,
		int $license_tier_rank
	): ?Feature {
		$catalog_type = $catalog_feature->get_type();
		$class        = $this->type_map[ $catalog_type ] ?? null;

		if ( $class === null ) {
			return null;
		}

		$minimum_tier = $product->get_tier_by_slug( $catalog_feature->get_minimum_tier() );
		$minimum_rank = $minimum_tier !== null ? $minimum_tier->get_rank() : PHP_INT_MAX;
		$is_available = $license_tier_rank >= $minimum_rank;

		$data = [
			'slug'              => $catalog_feature->get_feature_slug(),
			'group'             => $product->get_product_slug(),
			'tier'              => $catalog_feature->get_minimum_tier(),
			'name'              => $catalog_feature->get_name(),
			'description'       => $catalog_feature->get_description(),
			'type'              => $catalog_type,
			'is_available'      => $is_available,
			'documentation_url' => $catalog_feature->get_documentation_url(),
			'plugin_file'       => $catalog_feature->get_plugin_file() ?? '',
		];

		return $class::from_array( $data );
	}
}
