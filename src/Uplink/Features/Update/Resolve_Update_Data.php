<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Update;

use StellarWP\Uplink\Catalog\Catalog_Collection;
use StellarWP\Uplink\Catalog\Catalog_Repository;
use StellarWP\Uplink\Catalog\Results\Catalog_Feature;
use StellarWP\Uplink\Features\Contracts\Installable;
use StellarWP\Uplink\Features\Feature_Repository;
use WP_Error;

/**
 * Resolves update data by joining the Feature_Repository and Catalog.
 *
 * The Feature_Repository determines which features the site is licensed for
 * (availability). The Catalog provides the download URL and latest version.
 *
 * Only features where is_available() returns true are included,
 * ensuring the update API only serves updates the site is licensed for.
 * Dot-org features are excluded since WordPress.org serves their updates.
 *
 * Works for both Plugin and Theme features — the caller passes the desired
 * feature type constant, and the handler is responsible for reading any
 * type-specific fields (e.g. plugin_file, stylesheet) from the Feature object.
 *
 * @since 3.0.0
 */
class Resolve_Update_Data {

	/**
	 * The feature repository.
	 *
	 * @since 3.0.0
	 *
	 * @var Feature_Repository
	 */
	private Feature_Repository $feature_repository;

	/**
	 * The catalog repository.
	 *
	 * @since 3.0.0
	 *
	 * @var Catalog_Repository
	 */
	private Catalog_Repository $catalog_repository;

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 *
	 * @param Feature_Repository $feature_repository The feature repository.
	 * @param Catalog_Repository $catalog_repository The catalog repository.
	 */
	public function __construct(
		Feature_Repository $feature_repository,
		Catalog_Repository $catalog_repository
	) {
		$this->feature_repository = $feature_repository;
		$this->catalog_repository = $catalog_repository;
	}

	/**
	 * Fetches available Installable features of the given type and transforms them into update data.
	 *
	 * Joins feature availability from the Feature_Repository with download
	 * URLs and versions from the Catalog_Repository.
	 *
	 * @since 3.0.0
	 *
	 * @param string $key    The unified license key.
	 * @param string $domain The site domain.
	 * @param string $type   The feature type to resolve (a Feature::TYPE_* constant).
	 *
	 * @return array<string, array<string, mixed>>|WP_Error Keyed by slug, each entry contains update fields.
	 */
	public function __invoke( string $key, string $domain, string $type ) {
		$features = $this->feature_repository->get( $key, $domain );

		if ( is_wp_error( $features ) ) {
			return $features;
		}

		$catalog = $this->catalog_repository->get();

		if ( is_wp_error( $catalog ) ) {
			return $catalog;
		}

		$catalog_features = $this->build_catalog_feature_map( $catalog );

		$available = $features->filter( null, null, true, $type );

		$updates = [];

		foreach ( $available as $feature ) {
			if ( ! $feature instanceof Installable ) {
				continue;
			}

			$slug            = $feature->get_slug();
			$catalog_feature = $catalog_features[ $slug ] ?? null;

			if ( $catalog_feature === null || $catalog_feature->is_dot_org() ) {
				continue;
			}

			$updates[ $slug ] = [
				'name'     => $feature->get_name(),
				'slug'     => $slug,
				'version'  => $catalog_feature->get_version() ?? '',
				'package'  => $catalog_feature->get_download_url() ?? '',
				'url'      => $feature->get_documentation_url(),
				'author'   => implode( ', ', $feature->get_authors() ),
				'sections' => [
					'description' => $feature->get_description(),
				],
			];
		}

		return $updates;
	}

	/**
	 * Builds a flat map of feature slug to Catalog_Feature from the catalog.
	 *
	 * @since 3.0.0
	 *
	 * @param Catalog_Collection $catalog The catalog collection.
	 *
	 * @return array<string, Catalog_Feature>
	 */
	private function build_catalog_feature_map( Catalog_Collection $catalog ): array {
		$map = [];

		foreach ( $catalog as $product ) {
			foreach ( $product->get_features() as $catalog_feature ) {
				$map[ $catalog_feature->get_feature_slug() ] = $catalog_feature;
			}
		}

		return $map;
	}
}
