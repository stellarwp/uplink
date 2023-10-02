<?php declare( strict_types=1 );

namespace StellarWP\Uplink\API\V3\Auth;

use InvalidArgumentException;

/**
 * Auth URL cache decorator.
 */
final class Auth_Url_Cache_Decorator implements Contracts\Auth_Url {

	public const TRANSIENT_PREFIX = 'stellarwp_auth_url_';

	/**
	 * @var Auth_Url
	 */
	private $auth_url;

	/**
	 * The cache expiration in seconds.
	 *
	 * @var int
	 */
	private $expiration;

	public function __construct( Auth_Url $auth_url, int $expiration = DAY_IN_SECONDS ) {
		$this->auth_url   = $auth_url;
		$this->expiration = $expiration;
	}

	/**
	 * Cache the auth url response.
	 *
	 * @param  string  $slug  The product slug.
	 *
	 * @throws InvalidArgumentException
	 *
	 * @return string
	 */
	public function get( string $slug ): string {
		if ( ! $slug ) {
			throw new InvalidArgumentException( 'The Product Slug cannot be empty' );
		}

		$transient = $this->build_transient( $slug );

		$url = get_transient( $transient );

		if ( $url !== false ) {
			return $url;
		}

		$url = $this->auth_url->get( $slug );

		// We'll cache empty auth URLs to prevent further remote requests.
		set_transient( $transient, $url, $this->expiration );

		return $url;
	}

	/**
	 * Build the transient key based on the provided slug.
	 *
	 * @param  string  $slug
	 *
	 * @return string
	 */
	private function build_transient( string $slug ): string {
		return self::TRANSIENT_PREFIX . str_replace( '-', '_', $slug );
	}

}
