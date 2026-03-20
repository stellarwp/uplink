<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features;

use StellarWP\Uplink\Catalog\Catalog_Repository;
use StellarWP\Uplink\Catalog\Results\Catalog_Feature;
use StellarWP\Uplink\Catalog\Results\Product_Catalog;
use StellarWP\Uplink\Features\Contracts\Installable;
use StellarWP\Uplink\Features\Types\Feature;
use StellarWP\Uplink\Licensing\License_Manager;
use StellarWP\Uplink\Licensing\Product_Collection;
use StellarWP\Uplink\Site\Data;
use StellarWP\Uplink\Traits\With_Debugging;
use WP_Error;

/**
 * Joins catalog and licensing data to produce a resolved Feature_Collection.
 *
 * For each catalog feature, computes is_available by checking whether
 * the feature's slug appears in the product entry's capabilities array.
 * When no license exists, only free-tier features (minimum tier rank 0) are available.
 *
 * @since 3.0.0
 */
class Resolve_Feature_Collection {

	use With_Debugging;

	/**
	 * The catalog repository.
	 *
	 * @since 3.0.0
	 *
	 * @var Catalog_Repository
	 */
	private Catalog_Repository $catalog;

	/**
	 * The license manager.
	 *
	 * @since 3.0.0
	 *
	 * @var License_Manager
	 */
	private License_Manager $licensing;

	/**
	 * The site data provider.
	 *
	 * @since 3.0.0
	 *
	 * @var Data
	 */
	private Data $site_data;

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
	 * @param Catalog_Repository $catalog   The catalog repository.
	 * @param License_Manager    $licensing The license manager.
	 * @param Data               $site_data The site data provider.
	 */
	public function __construct(
		Catalog_Repository $catalog,
		License_Manager $licensing,
		Data $site_data
	) {
		$this->catalog   = $catalog;
		$this->licensing = $licensing;
		$this->site_data = $site_data;
	}

	/**
	 * Registers a Feature subclass for a given catalog type string.
	 *
	 * @since 3.0.0
	 *
	 * @param string                $type          A Feature::TYPE_* constant (e.g. Feature::TYPE_PLUGIN).
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
	 * @return Feature_Collection|WP_Error
	 */
	public function __invoke() {
		$catalog = $this->catalog->get();

		if ( is_wp_error( $catalog ) ) {
			static::debug_log_wp_error(
				$catalog,
				'Catalog fetch failed during feature resolution'
			);

			return $catalog;
		}

		$products = $this->licensing->get_products( $this->site_data->get_domain() );

		if ( is_wp_error( $products ) ) {
			if ( $this->licensing->get_key() === null ) {
				$products = new Product_Collection();
			} else {
				static::debug_log_wp_error(
					$products,
					'Licensing fetch failed during feature resolution'
				);

				return $products;
			}
		}

		$collection = new Feature_Collection();

		foreach ( $catalog as $product ) {
			if ( ! $product instanceof Product_Catalog ) {
				continue;
			}

			$capabilities = $this->resolve_capabilities( $product, $products );

			foreach ( $product->get_features() as $catalog_feature ) {
				$feature = $this->hydrate_feature( $catalog_feature, $product, $capabilities );

				if ( is_wp_error( $feature ) ) {
					static::debug_log( $feature->get_error_message() );
					continue;
				}

				$collection->add( $feature );
			}
		}

		return $collection;
	}

	/**
	 * Resolves the capabilities granted by the license for a given product.
	 *
	 * Returns the capabilities array from the product entry when a license exists,
	 * or null when no license is present for this product.
	 *
	 * @since 3.0.0
	 *
	 * @param Product_Catalog    $product  The catalog product.
	 * @param Product_Collection $products The licensing product collection.
	 *
	 * @return string[]|null The capabilities array, or null if the product has no license.
	 */
	private function resolve_capabilities( Product_Catalog $product, Product_Collection $products ): ?array {
		$license = $products->get( $product->get_product_slug() );

		if ( null === $license ) {
			return null;
		}

		return $license->get_capabilities();
	}

	/**
	 * Hydrates a Feature object from a catalog feature entry.
	 *
	 * Maps catalog types (plugin, theme, flag) to Feature subclasses
	 * (Plugin, Theme, Flag) and computes is_available from the capabilities array.
	 *
	 * When capabilities is null (no license), only free-tier features
	 * (minimum tier rank 0) are available.
	 *
	 * @since 3.0.0
	 *
	 * @param Catalog_Feature $catalog_feature The catalog feature entry.
	 * @param Product_Catalog $product         The parent catalog product.
	 * @param string[]|null   $capabilities    The license capabilities, or null if unlicensed.
	 *
	 * @return Feature|WP_Error The hydrated feature, or WP_Error for unknown types.
	 */
	private function hydrate_feature(
		Catalog_Feature $catalog_feature,
		Product_Catalog $product,
		?array $capabilities
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

		if ( $catalog_feature->is_dot_org() ) {
			$is_available = true;
		} elseif ( $capabilities === null ) {
			$minimum_tier = $product->get_tier_by_slug( $catalog_feature->get_minimum_tier() );
			$minimum_rank = $minimum_tier !== null ? $minimum_tier->get_rank() : PHP_INT_MAX;
			$is_available = $minimum_rank === 0;
		} else {
			$is_available = in_array( $catalog_feature->get_feature_slug(), $capabilities, true );
		}

		$data = [
			'slug'              => $catalog_feature->get_feature_slug(),
			'product'           => $product->get_product_slug(),
			'tier'              => $catalog_feature->get_minimum_tier(),
			'name'              => $catalog_feature->get_name(),
			'description'       => $catalog_feature->get_description(),
			'type'              => $catalog_type,
			'is_available'      => $is_available,
			'documentation_url' => $catalog_feature->get_documentation_url(),
			'released_at'       => $catalog_feature->get_released_at(),
			'plugin_file'       => $catalog_feature->get_plugin_file() ?? '',
			'is_dot_org'        => $catalog_feature->is_dot_org(),
			'authors'           => $catalog_feature->get_authors() ?? [],
			'version'           => $catalog_feature->get_version(),
			'changelog'         => $catalog_feature->get_changelog(),
		];

		$feature = $class::from_array( $data );

		if ( $feature instanceof Installable ) {
			$data['installed_version'] = $feature->get_installed_version();
			$feature                   = $class::from_array( $data );
		}

		return $feature;
	}
}
