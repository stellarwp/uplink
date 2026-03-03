<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Licensing;

use StellarWP\Uplink\Licensing\Contracts\Licensing_Client;
use WP_Error;

/**
 * Transient-cached repository for the v4 licensing product catalog.
 *
 * This is the public API that the rest of Uplink uses — it never
 * exposes the client directly.
 *
 * Only one unified license key exists per site, so the cache stores
 * a single catalog keyed by a fixed transient name.
 *
 * @since 3.0.0
 */
class Product_Repository {

	/**
	 * Transient key for the cached product catalog.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const TRANSIENT_KEY = 'stellarwp_uplink_licensing_products';

	/**
	 * Default cache duration in seconds (12 hours).
	 *
	 * @since 3.0.0
	 *
	 * @var int
	 */
	private const CACHE_DURATION = 43200;

	/**
	 * The licensing client.
	 *
	 * @since 3.0.0
	 *
	 * @var Licensing_Client
	 */
	protected Licensing_Client $client;

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 *
	 * @param Licensing_Client $client The licensing client to fetch from.
	 */
	public function __construct( Licensing_Client $client ) {
		$this->client = $client;
	}

	/**
	 * Gets the product catalog, using the transient cache when available.
	 *
	 * @since 3.0.0
	 *
	 * @param string $key    License key.
	 * @param string $domain Site domain.
	 *
	 * @return Product_Collection|WP_Error
	 */
	public function get( string $key, string $domain ) {
		$cached = get_transient( self::TRANSIENT_KEY );

		if ( $cached instanceof Product_Collection || is_wp_error( $cached ) ) {
			return $cached;
		}

		return $this->fetch( $key, $domain );
	}

	/**
	 * Deletes the transient cache and re-fetches from the client.
	 *
	 * @since 3.0.0
	 *
	 * @param string $key    License key.
	 * @param string $domain Site domain.
	 *
	 * @return Product_Collection|WP_Error
	 */
	public function refresh( string $key, string $domain ) {
		delete_transient( self::TRANSIENT_KEY );

		return $this->fetch( $key, $domain );
	}

	/**
	 * Fetches from the client and caches the result.
	 *
	 * @since 3.0.0
	 *
	 * @param string $key    License key.
	 * @param string $domain Site domain.
	 *
	 * @return Product_Collection|WP_Error
	 */
	protected function fetch( string $key, string $domain ) {
		$result = $this->client->get_products( $key, $domain );

		if ( is_wp_error( $result ) ) {
			set_transient( self::TRANSIENT_KEY, $result, self::CACHE_DURATION );

			return $result;
		}

		$collection = Product_Collection::from_array( $result );

		set_transient( self::TRANSIENT_KEY, $collection, self::CACHE_DURATION );

		return $collection;
	}
}
