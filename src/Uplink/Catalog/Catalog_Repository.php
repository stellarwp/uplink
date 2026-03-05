<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Catalog;

use StellarWP\Uplink\Catalog\Contracts\Catalog_Client;
use WP_Error;

/**
 * Transient-cached repository for the product catalog.
 *
 * This is the public API that the rest of Uplink uses — it never
 * exposes the client directly.
 *
 * @since 3.0.0
 */
final class Catalog_Repository {

	/**
	 * Transient key for the cached catalog.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const TRANSIENT_KEY = 'stellarwp_uplink_catalog';

	/**
	 * Default cache duration in seconds (12 hours).
	 *
	 * @since 3.0.0
	 *
	 * @var int
	 */
	private const CACHE_DURATION = HOUR_IN_SECONDS * 12;

	/**
	 * The catalog client.
	 *
	 * @since 3.0.0
	 *
	 * @var Catalog_Client
	 */
	protected Catalog_Client $client;

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 *
	 * @param Catalog_Client $client The catalog client to fetch from.
	 */
	public function __construct( Catalog_Client $client ) {
		$this->client = $client;
	}

	/**
	 * Gets the full catalog, using the transient cache when available.
	 *
	 * @since 3.0.0
	 *
	 * @return Catalog_Collection|WP_Error
	 */
	public function get() {
		$cached = get_transient( self::TRANSIENT_KEY );

		if ( is_wp_error( $cached ) ) {
			return $cached;
		}

		if ( is_array( $cached ) ) {
			/** @var array<int, array<string, mixed>> $cached */
			return Catalog_Collection::from_array( $cached );
		}

		return $this->fetch();
	}

	/**
	 * Deletes the transient cache and re-fetches from the client.
	 *
	 * @since 3.0.0
	 *
	 * @return Catalog_Collection|WP_Error
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
	 * @return Catalog_Collection|WP_Error
	 */
	protected function fetch() {
		$result = $this->client->get_catalog();

		if ( $result instanceof Catalog_Collection ) {
			$data = [];

			foreach ( $result as $catalog ) {
				$data[] = $catalog->to_array();
			}

			set_transient( self::TRANSIENT_KEY, $data, self::CACHE_DURATION );
		} else {
			set_transient( self::TRANSIENT_KEY, $result, self::CACHE_DURATION );
		}

		return $result;
	}
}
