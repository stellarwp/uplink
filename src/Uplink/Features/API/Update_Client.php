<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\API;

use WP_Error;

/**
 * Queries the server for available updates and
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
	 * Deletes the transient cache and re-fetches from the API.
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
	 * Fetches updates from the server and caches the result.
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
		$response = $this->request( $unified_key, $domain, $products );

		set_transient( self::TRANSIENT_KEY, $response, self::DEFAULT_CACHE_DURATION );

		return $response;
	}

	/**
	 * Performs the HTTP request to the server.
	 *
	 * @since 3.0.0
	 *
	 * @param string                $unified_key The unified license key.
	 * @param string                $domain      The site domain.
	 * @param array<string, string> $products    Map of slug => installed_version.
	 *
	 * @return array<string, array<string, mixed>>|WP_Error Keyed by slug, each entry contains update fields.
	 *
	 * @phpstan-ignore-next-line return.unusedType -- Remove once the API request is implemented.
	 */
	private function request( string $unified_key, string $domain, array $products ) {
		// TODO: Implement the actual HTTP request once the server contract is known.
		// Should send unified license key, site domain, and product versions.
		return [];
	}
}
