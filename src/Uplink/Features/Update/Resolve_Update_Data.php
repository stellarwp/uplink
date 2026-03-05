<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Update;

use StellarWP\Uplink\Features\Feature_Repository;
use StellarWP\Uplink\Features\Types\Plugin;
use WP_Error;

/**
 * Resolves update data from the Feature_Repository.
 *
 * Fetches available Plugin features and transforms them into
 * WordPress-compatible update data keyed by feature slug.
 *
 * Only features where is_available() returns true are included,
 * ensuring plugins_api() only serves updates the site is licensed for.
 * Dot-org features are excluded since WordPress.org serves their updates.
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
	 * Constructor.
	 *
	 * @since 3.0.0
	 *
	 * @param Feature_Repository $feature_repository The feature repository.
	 */
	public function __construct( Feature_Repository $feature_repository ) {
		$this->feature_repository = $feature_repository;
	}

	/**
	 * Fetches available Plugin features and transforms them into update data.
	 *
	 * @since 3.0.0
	 *
	 * @param string $key    The unified license key.
	 * @param string $domain The site domain.
	 *
	 * @return array<string, array<string, mixed>>|WP_Error Keyed by slug, each entry contains update fields.
	 */
	public function __invoke( string $key, string $domain ) {
		$features = $this->feature_repository->get( $key, $domain );

		if ( is_wp_error( $features ) ) {
			return $features;
		}

		$available_plugins = $features->filter( null, null, true, 'plugin' );

		$updates = [];

		foreach ( $available_plugins as $feature ) {
			if (
				! $feature instanceof Plugin
				|| $feature->is_dot_org()
			) {
				continue;
			}

			$slug = $feature->get_slug();

			$updates[ $slug ] = [
				'name'        => $feature->get_name(),
				'slug'        => $slug,
				'new_version' => $feature->get_new_version() ?? '',
				'package'     => $feature->get_download_url(),
				'url'         => $feature->get_documentation_url(),
				'author'      => implode( ', ', $feature->get_authors() ),
				'plugin_file' => $feature->get_plugin_file(),
				'sections'    => [
					'description' => $feature->get_description(),
				],
			];
		}

		return $updates;
	}
}
