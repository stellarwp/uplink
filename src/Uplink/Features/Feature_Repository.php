<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features;

use StellarWP\Uplink\Features\Contracts\Feature_Client;
use WP_Error;

/**
 * Transient-cached repository for the feature catalog.
 *
 * This is the public API that the rest of Uplink uses — it never
 * exposes the client directly.
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
	 * The feature client.
	 *
	 * @since 3.0.0
	 *
	 * @var Feature_Client
	 */
	protected Feature_Client $client;

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 *
	 * @param Feature_Client $client The feature client to fetch from.
	 */
	public function __construct( Feature_Client $client ) {
		$this->client = $client;
	}

	/**
	 * Gets the full feature catalog, using the transient cache when available.
	 *
	 * @since 3.0.0
	 *
	 * @return Feature_Collection|WP_Error
	 */
	public function get() {
		$cached = get_transient( self::TRANSIENT_KEY );

		if ( is_wp_error( $cached ) ) {
			return $cached;
		}

		if ( $cached instanceof Feature_Collection ) {
			return $cached;
		}

		return $this->fetch();
	}

	/**
	 * Deletes the transient cache and re-fetches from the client.
	 *
	 * @since 3.0.0
	 *
	 * @return Feature_Collection|WP_Error
	 */
	public function refresh() {
		delete_transient( self::TRANSIENT_KEY );

		return $this->fetch();
	}

	/**
	 * Fetches from the client and caches the result.
	 *
	 * @since 3.0.0
	 *
	 * @return Feature_Collection|WP_Error
	 */
	protected function fetch() {
		$result = $this->client->get_features();

		set_transient( self::TRANSIENT_KEY, $result, self::CACHE_DURATION );

		return $result;
	}
}
