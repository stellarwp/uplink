<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Update;

use WP_Error;

/**
 * Transient-cached repository for update data.
 *
 * Delegates resolution to Resolve_Update_Data and caches the result
 * in a WordPress transient.
 *
 * @since 3.0.0
 */
class Update_Repository {

	/**
	 * Transient key for the cached update data.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const TRANSIENT_KEY = 'stellarwp_uplink_update_check';

	/**
	 * Default cache duration in seconds (12 hours).
	 *
	 * @since 3.0.0
	 *
	 * @var int
	 */
	private const CACHE_DURATION = HOUR_IN_SECONDS * 12;

	/**
	 * The update data resolver.
	 *
	 * @since 3.0.0
	 *
	 * @var Resolve_Update_Data
	 */
	private Resolve_Update_Data $resolver;

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 *
	 * @param Resolve_Update_Data $resolver The update data resolver.
	 */
	public function __construct( Resolve_Update_Data $resolver ) {
		$this->resolver = $resolver;
	}

	/**
	 * Gets the update data, using the transient cache when available.
	 *
	 * @since 3.0.0
	 *
	 * @param string $key    The unified license key.
	 * @param string $domain The site domain.
	 *
	 * @return array<string, array<string, mixed>>|WP_Error Keyed by slug, each entry contains update fields.
	 */
	public function get( string $key, string $domain ) {
		$cached = get_transient( self::TRANSIENT_KEY );

		if ( is_wp_error( $cached ) ) {
			return $cached;
		}

		if ( is_array( $cached ) ) {
			/** @var array<string, array<string, mixed>> $cached */
			return $cached;
		}

		return $this->resolve( $key, $domain );
	}

	/**
	 * Deletes the transient cache and re-resolves.
	 *
	 * @since 3.0.0
	 *
	 * @param string $key    The unified license key.
	 * @param string $domain The site domain.
	 *
	 * @return array<string, array<string, mixed>>|WP_Error Keyed by slug, each entry contains update fields.
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
	 * @param string $key    The unified license key.
	 * @param string $domain The site domain.
	 *
	 * @return array<string, array<string, mixed>>|WP_Error Keyed by slug, each entry contains update fields.
	 */
	protected function resolve( string $key, string $domain ) {
		$result = ( $this->resolver )( $key, $domain );

		set_transient( self::TRANSIENT_KEY, $result, self::CACHE_DURATION );

		return $result;
	}
}
