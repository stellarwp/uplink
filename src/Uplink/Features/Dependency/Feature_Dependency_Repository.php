<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Dependency;

use StellarWP\Uplink\Features\Dependency\Clients\Dependency_Client;

/**
 * Resolves feature dependencies via a Dependency_Client.
 *
 * @since 3.0.0
 */
class Feature_Dependency_Repository {

	/**
	 * The dependency API client.
	 *
	 * @since 3.0.0
	 *
	 * @var Dependency_Client
	 */
	private Dependency_Client $client;

	/**
	 * In-memory cache of the resolved collection.
	 *
	 * Null until first fetch. WP_Error is stored so we don't retry on failure
	 * within the same request.
	 *
	 * @since 3.0.0
	 *
	 * @var Dependency_Collection|null
	 */
	private $collection = null;

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 *
	 * @param Dependency_Client $client The dependency client.
	 */
	public function __construct( Dependency_Client $client ) {
		$this->client = $client;
	}

	/**
	 * Returns the dependency list for a given feature slug and version.
	 *
	 * Lookup order:
	 *   1. Exact version match (e.g. "1.2.3")
	 *   2. "default" version entry
	 *   3. Empty array
	 *
	 * @since 3.0.0
	 *
	 * @param string $feature_slug    The feature slug to look up.
	 * @param string $feature_version The feature version (e.g. "1.2.3").
	 *
	 * @return Dependency[] Empty array if no dependencies are declared.
	 */
	public function get( string $feature_slug, string $feature_version ): array {
		$collection = $this->get_collection();

		if ( $collection === null ) {
			return [];
		}

		return $collection->get( $feature_slug, $feature_version );
	}

	/**
	 * Returns the resolved collection, fetching once per request.
	 *
	 * Returns null when the client returns a WP_Error.
	 *
	 * @since 3.0.0
	 *
	 * @return Dependency_Collection|null
	 */
	private function get_collection(): ?Dependency_Collection {
		if ( $this->collection !== null ) {
			return $this->collection;
		}

		$result = $this->client->get_dependencies();

		if ( is_wp_error( $result ) ) {
			return null;
		}

		$this->collection = $result;

		return $this->collection;
	}
}
