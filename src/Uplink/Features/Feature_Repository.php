<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features;

use WP_Error;

/**
 * Manages caching and delegates feature resolution to Resolve_Feature_Collection.
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
	 * The feature collection resolver.
	 *
	 * @since 3.0.0
	 *
	 * @var Resolve_Feature_Collection
	 */
	private Resolve_Feature_Collection $resolver;

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 *
	 * @param Resolve_Feature_Collection $resolver The feature collection resolver.
	 */
	public function __construct( Resolve_Feature_Collection $resolver ) {
		$this->resolver = $resolver;
	}

	/**
	 * Gets the resolved feature collection, using the transient cache when available.
	 *
	 * @since 3.0.0
	 *
	 * @param string $key    License key.
	 * @param string $domain Site domain.
	 *
	 * @return Feature_Collection|WP_Error
	 */
	public function get( string $key, string $domain ) {
		$cached = get_transient( self::TRANSIENT_KEY );

		if ( is_wp_error( $cached ) ) {
			return $cached;
		}

		if ( is_array( $cached ) ) {
			/** @var array<int, array<string, mixed>> $cached */
			return Feature_Collection::from_array( $cached );
		}

		return $this->resolve( $key, $domain );
	}

	/**
	 * Deletes the transient cache and re-resolves.
	 *
	 * @since 3.0.0
	 *
	 * @param string $key    License key.
	 * @param string $domain Site domain.
	 *
	 * @return Feature_Collection|WP_Error
	 */
	public function refresh( string $key, string $domain ) {
		delete_transient( self::TRANSIENT_KEY );

		return $this->resolve( $key, $domain );
	}

	/**
	 * Delegates resolution to the resolver and caches the result.
	 *
	 * @since 3.0.0
	 *
	 * @param string $key    License key.
	 * @param string $domain Site domain.
	 *
	 * @return Feature_Collection|WP_Error
	 */
	protected function resolve( string $key, string $domain ) {
		$result = ( $this->resolver )( $domain );

		if ( $result instanceof Feature_Collection ) {
			set_transient( self::TRANSIENT_KEY, $result->to_array(), self::CACHE_DURATION );
		} else {
			set_transient( self::TRANSIENT_KEY, $result, self::CACHE_DURATION );
		}

		return $result;
	}
}
