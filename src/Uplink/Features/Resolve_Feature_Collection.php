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
 * For each catalog feature, computes is_available by comparing
 * the site's licensed tier rank against the feature's minimum tier rank.
 *
 * @since 3.0.0
 */
class Resolve_Feature_Collection {

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
	 * Map of catalog type strings to Feature subclass names.
	 *
	 * @since 3.0.0
	 *
	 * @var array<string, class-string<Feature>>
	 */
	private array $type_map = [];

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 *
	 * @param Catalog_Repository $catalog  The catalog repository.
	 * @param Product_Repository $licensing The licensing product repository.
	 */
	public function __construct(
		Catalog_Repository $catalog,
		Product_Repository $licensing
	) {
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
	 * Fetches catalog and licensing data and resolves them into a Feature_Collection.
	 *
	 * Iterates each catalog product, finds the matching license entry,
	 * and hydrates Feature objects with computed is_available values.
	 *
	 * @since 3.0.0
	 *
	 * @param string $key    License key.
	 * @param string $domain Site domain.
	 *
	 * @return Feature_Collection|WP_Error
	 */
	public function __invoke( string $key, string $domain ) {
		$catalog = $this->catalog->get();

		if ( is_wp_error( $catalog ) ) {
			return $catalog;
		}

		$products = $this->licensing->get( $key, $domain );

		if ( is_wp_error( $products ) ) {
			return $products;
		}

		$collection = new Feature_Collection();

		foreach ( $catalog as $product ) {
			if ( ! $product instanceof Product_Catalog ) {
				continue;
			}

			$license_tier_rank = $this->resolve_license_tier_rank( $product, $products );

			foreach ( $product->get_features() as $catalog_feature ) {
				$feature = $this->hydrate_feature( $catalog_feature, $product, $license_tier_rank );

				if ( is_wp_error( $feature ) ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentionally logging.
					error_log( sprintf( 'Uplink: %s', $feature->get_error_message() ) );
					continue;
				}

				$collection->add( $feature );
			}
		}

		return $collection;
	}

	/**
	 * Resolves the licensed tier rank for a given product.
	 *
	 * Looks up the license's tier slug directly in the product's tier collection.
	 * Both the catalog and licensing fixtures use the same product-prefixed
	 * tier slug convention (e.g. "kadence-pro").
	 *
	 * @since 3.0.0
	 *
	 * @param Product_Catalog    $product  The catalog product.
	 * @param Product_Collection $products The licensing product collection.
	 *
	 * @return int The tier rank, or 0 if the product has no license.
	 */
	private function resolve_license_tier_rank( Product_Catalog $product, Product_Collection $products ): int {
		$license = $products->get( $product->get_product_slug() );

		if ( $license === null ) {
			return 0;
		}

		$tier = $product->get_tier_by_slug( $license->get_tier() );

		return $tier !== null ? $tier->get_rank() : 0;
	}

	/**
	 * Hydrates a Feature object from a catalog feature entry.
	 *
	 * Maps catalog types (plugin, flag, theme) to Feature subclasses
	 * (Zip, Flag) and computes is_available from tier rank comparison.
	 *
	 * @since 3.0.0
	 *
	 * @param Catalog_Feature $catalog_feature   The catalog feature entry.
	 * @param Product_Catalog $product           The parent catalog product.
	 * @param int             $license_tier_rank The resolved license tier rank.
	 *
	 * @return Feature|WP_Error The hydrated feature, or WP_Error for unknown types.
	 */
	private function hydrate_feature(
		Catalog_Feature $catalog_feature,
		Product_Catalog $product,
		int $license_tier_rank
	) {
		$catalog_type = $catalog_feature->get_type();
		$class        = $this->type_map[ $catalog_type ] ?? null;

		if ( $class === null ) {
			return new WP_Error(
				Error_Code::UNKNOWN_FEATURE_TYPE,
				sprintf(
					'No Feature subclass registered for catalog type "%s" (feature: %s).',
					$catalog_type,
					$catalog_feature->get_feature_slug()
				)
			);
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
