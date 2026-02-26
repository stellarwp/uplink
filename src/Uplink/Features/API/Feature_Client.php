<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\API;

use StellarWP\Uplink\Features\Feature_Collection;
use StellarWP\Uplink\Features\Types\Feature;
use StellarWP\Uplink\Utils\Cast;
use WP_Error;

/**
 * Fetches the feature catalog from the Commerce Portal API and
 * caches the result as a WordPress transient.
 *
 * @since 3.0.0
 */
class Feature_Client {

	/**
	 * Transient key for the cached feature catalog.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	private const TRANSIENT_KEY = 'stellarwp_uplink_feature_catalog';

	/**
	 * Default cache duration in seconds (12 hours).
	 *
	 * @since 3.0.0
	 *
	 * @var int
	 */
	private const DEFAULT_CACHE_DURATION = HOUR_IN_SECONDS * 12;

	/**
	 * Map of feature type strings to Feature subclass names.
	 *
	 * @since 3.0.0
	 *
	 * @var array<string, class-string<Feature>>
	 */
	private array $type_map = [];

	/**
	 * Registers a Feature subclass for a given type string.
	 *
	 * @since 3.0.0
	 *
	 * @param string                $type          The feature type identifier (e.g. 'zip', 'built_in').
	 * @param class-string<Feature> $feature_class The Feature subclass FQCN.
	 *
	 * @return void
	 */
	public function register_type( string $type, string $feature_class ): void { // phpcs:ignore Squiz.Commenting.FunctionComment.IncorrectTypeHint -- class-string<Feature> is a PHPStan type narrowing.
		$this->type_map[ $type ] = $feature_class;
	}

	/**
	 * Gets the feature collection, using the transient cache when available.
	 *
	 * @since 3.0.0
	 *
	 * @return Feature_Collection|WP_Error
	 */
	public function get_features() {
		$cached = get_transient( self::TRANSIENT_KEY );

		if ( $cached instanceof Feature_Collection || is_wp_error( $cached ) ) {
			return $cached;
		}

		return $this->fetch_features();
	}

	/**
	 * Deletes the transient cache and re-fetches from the API.
	 *
	 * @since 3.0.0
	 *
	 * @return Feature_Collection|WP_Error
	 */
	public function refresh() {
		delete_transient( self::TRANSIENT_KEY );

		return $this->fetch_features();
	}

	/**
	 * Fetches features from the Commerce Portal API and caches the result.
	 *
	 * @since 3.0.0
	 *
	 * @return Feature_Collection|WP_Error
	 */
	private function fetch_features() {
		$response = $this->request();

		if ( is_wp_error( $response ) ) {
			set_transient( self::TRANSIENT_KEY, $response, self::DEFAULT_CACHE_DURATION );

			return $response;
		}

		$collection = $this->hydrate( $response );

		set_transient( self::TRANSIENT_KEY, $collection, self::DEFAULT_CACHE_DURATION );

		return $collection;
	}

	/**
	 * Performs the HTTP request to the Commerce Portal API.
	 *
	 * @since 3.0.0
	 *
	 * @return array<int, array<string, mixed>>|WP_Error The decoded response entries or an error.
	 *
	 * @phpstan-ignore-next-line return.unusedType -- Remove once the API request is implemented.
	 */
	private function request() {
		// TODO: Implement the actual API request to Commerce Portal.
		// Should send site domain + license keys and return the feature catalog.
		return [];
	}

	/**
	 * Hydrates Feature objects from the API response using a type-to-class map.
	 *
	 * @since 3.0.0
	 *
	 * @param array<int, array<string, mixed>> $response The API response entries.
	 *
	 * @return Feature_Collection
	 */
	private function hydrate( array $response ): Feature_Collection {
		$collection = new Feature_Collection();

		foreach ( $response as $entry ) {
			$type  = isset( $entry['type'] ) && is_string( $entry['type'] ) ? $entry['type'] : null;
			$class = $type !== null ? ( $this->type_map[ $type ] ?? null ) : null;

			if ( $class === null ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentionally logging.
					error_log(
						sprintf(
							"Uplink: Unknown feature type '%s' for slug '%s'",
							$type ?? '(null)',
							Cast::to_string( $entry['slug'] ?? '(unknown)' )
						)
					);
				}
				continue;
			}

			$collection->add( $class::from_array( $entry ) );
		}

		return $collection;
	}
}
