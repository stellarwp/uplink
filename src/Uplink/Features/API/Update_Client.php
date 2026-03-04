<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\API;

use StellarWP\Uplink\Features\Feature_Repository;
use StellarWP\Uplink\Features\Types\Zip;
use WP_Error;

/**
 * Queries the Feature_Repository for available updates and
 * caches the result as a WordPress transient.
 *
 * @since 3.0.0
 */
class Update_Client {

	/**
	 * Transient key for the cached update response.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	private const TRANSIENT_KEY = 'stellarwp_uplink_update_check';

	/**
	 * Default cache duration in seconds (12 hours).
	 *
	 * @since 3.0.0
	 *
	 * @var int
	 */
	private const DEFAULT_CACHE_DURATION = HOUR_IN_SECONDS * 12;

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
	 * Gets the update data, using the transient cache when available.
	 *
	 * @since 3.0.0
	 *
	 * @param string                $unified_key The unified license key.
	 * @param string                $domain      The site domain.
	 * @param array<string, string> $products    Map of slug => installed_version.
	 *
	 * @return array<string, array<string, mixed>>|WP_Error Keyed by slug, each entry contains update fields.
	 */
	public function check_updates( string $unified_key, string $domain, array $products ) {
		$cached = get_transient( self::TRANSIENT_KEY );

		if ( is_wp_error( $cached ) ) {
			return $cached;
		}

		if ( is_array( $cached ) ) {
			/** @var array<string, array<string, mixed>> $cached */
			return $cached;
		}

		return $this->fetch_updates( $unified_key, $domain, $products );
	}

	/**
	 * Deletes the transient cache and re-fetches from the Feature_Repository.
	 *
	 * @since 3.0.0
	 *
	 * @param string                $unified_key The unified license key.
	 * @param string                $domain      The site domain.
	 * @param array<string, string> $products    Map of slug => installed_version.
	 *
	 * @return array<string, array<string, mixed>>|WP_Error Keyed by slug, each entry contains update fields.
	 */
	public function refresh( string $unified_key, string $domain, array $products ) {
		delete_transient( self::TRANSIENT_KEY );

		return $this->fetch_updates( $unified_key, $domain, $products );
	}

	/**
	 * Fetches available Zip features from the Feature_Repository
	 * and transforms them into WordPress-compatible update data.
	 *
	 * Only features where is_available() returns true are included,
	 * ensuring plugins_api() only serves updates the site is licensed for.
	 *
	 * @since 3.0.0
	 *
	 * @param string                $unified_key The unified license key.
	 * @param string                $domain      The site domain.
	 * @param array<string, string> $products    Map of slug => installed_version.
	 *
	 * @return array<string, array<string, mixed>>|WP_Error Keyed by slug, each entry contains update fields.
	 */
	private function fetch_updates( string $unified_key, string $domain, array $products ) {
		$features = $this->feature_repository->get( $unified_key, $domain );

		if ( is_wp_error( $features ) ) {
			set_transient( self::TRANSIENT_KEY, $features, self::DEFAULT_CACHE_DURATION );

			return $features;
		}

		$available_zips = $features->filter( null, null, true, 'zip' );

		$updates = [];

		foreach ( $available_zips as $feature ) {
			if ( ! $feature instanceof Zip ) {
				continue;
			}

			$slug = $feature->get_slug();

			$updates[ $slug ] = [
				'name'        => $feature->get_name(),
				'slug'        => $slug,
				'new_version' => '',
				'package'     => $feature->get_download_url(),
				'url'         => $feature->get_documentation_url(),
				'author'      => implode( ', ', $feature->get_authors() ),
				'plugin_file' => $feature->get_plugin_file(),
				'sections'    => [
					'description' => $feature->get_description(),
				],
			];
		}

		set_transient( self::TRANSIENT_KEY, $updates, self::DEFAULT_CACHE_DURATION );

		return $updates;
	}
}
