<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\API;

use StellarWP\Uplink\Features\Collection;
use StellarWP\Uplink\Features\Types\Feature;

/**
 * Fetches the feature catalog from the Commerce Portal API and
 * caches the result as a WordPress transient.
 *
 * @since TBD
 */
class Client {

	/**
	 * Transient key for the cached feature catalog.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private const TRANSIENT_KEY = 'stellarwp_uplink_feature_catalog';

	/**
	 * Default cache duration in seconds (12 hours).
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	private const DEFAULT_CACHE_DURATION = 43200;

	/**
	 * Map of feature type strings to Feature subclass names.
	 *
	 * @since TBD
	 *
	 * @var array<string, class-string<Feature>>
	 */
	private array $type_map = [];

	/**
	 * Registers a Feature subclass for a given type string.
	 *
	 * @since TBD
	 *
	 * @param string                $type          The feature type identifier (e.g. 'zip', 'built_in').
	 * @param class-string<Feature> $feature_class The Feature subclass FQCN.
	 *
	 * @return void
	 */
	public function register_type( string $type, string $feature_class ): void {
		$this->type_map[ $type ] = $feature_class;
	}

	/**
	 * Gets the feature collection, using the transient cache when available.
	 *
	 * @since TBD
	 *
	 * @return Collection
	 */
	public function get_features(): Collection {
		$cached = get_transient( self::TRANSIENT_KEY );

		if ( $cached instanceof Collection ) {
			return $cached;
		}

		return $this->fetch_features();
	}

	/**
	 * Deletes the transient cache and re-fetches from the API.
	 *
	 * @since TBD
	 *
	 * @return Collection
	 */
	public function refresh(): Collection {
		delete_transient( self::TRANSIENT_KEY );

		return $this->fetch_features();
	}

	/**
	 * Fetches features from the Commerce Portal API and caches the result.
	 *
	 * @since TBD
	 *
	 * @return Collection
	 */
	private function fetch_features(): Collection {
		$response = $this->request();

		$collection = $this->hydrate( $response );

		set_transient( self::TRANSIENT_KEY, $collection, self::DEFAULT_CACHE_DURATION );

		return $collection;
	}

	/**
	 * Performs the HTTP request to the Commerce Portal API.
	 *
	 * @since TBD
	 *
	 * @return array<int, array<string, mixed>> The decoded response entries.
	 */
	private function request(): array {
		// TODO: Implement the actual API request to Commerce Portal.
		// Should send site domain + license keys and return the feature catalog.
		return [];
	}

	/**
	 * Hydrates Feature objects from the API response using a type-to-class map.
	 *
	 * @since TBD
	 *
	 * @param array<int, array<string, mixed>> $response The API response entries.
	 *
	 * @return Collection
	 */
	private function hydrate( array $response ): Collection {
		/**
		 * Filters the feature type to class map used during hydration.
		 *
		 * @since TBD
		 *
		 * @param array<string, class-string<Feature>> $type_map The current type map.
		 *
		 * @return array<string, class-string<Feature>> The filtered type map.
		 */
		$type_map = apply_filters( 'stellarwp/uplink/feature_type_map', $this->type_map );

		$collection = new Collection();

		foreach ( $response as $entry ) {
			$type  = $entry['type'] ?? null;
			$class = $type_map[ $type ] ?? null;

			if ( $class === null ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( sprintf(
						"Uplink: Unknown feature type '%s' for slug '%s'",
						$type ?? '(null)',
						$entry['slug'] ?? '(unknown)'
					) );
				}
				continue;
			}

			$collection->add( $class::from_array( $entry ) );
		}

		return $collection;
	}
}
