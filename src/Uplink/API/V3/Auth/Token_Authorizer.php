<?php declare( strict_types=1 );

namespace StellarWP\Uplink\API\V3\Auth;

use StellarWP\Uplink\API\V3\Contracts\Client_V3;
use StellarWP\Uplink\Traits\With_Debugging;
use WP_Error;
use WP_Http;

use function StellarWP\Uplink\is_authorized;

/**
 * Manages authorization.
 */
final class Token_Authorizer {

	use With_Debugging;

	/**
	 * @var Client_V3
	 */
	private $client;

	public function __construct( Client_V3 $client ) {
		$this->client = $client;
	}

	/**
	 * Manually check if a license is authorized.
	 *
	 * @see is_authorized()
	 *
	 * @param  string  $license  The license key.
	 * @param  string  $token  The stored token.
	 * @param  string  $domain  The user's domain.
	 *
	 * @return bool
	 */
	public function is_authorized( string $license, string $token, string $domain ): bool {
		$response = $this->client->get( 'tokens/auth', [
			'license' => $license,
			'token'   => $token,
			'domain'  => $domain,
		] );

		if ( $response instanceof WP_Error ) {
			if ( $this->is_wp_debug() ) {
				error_log( sprintf(
					'Authorization error occurred: License: "%s", Token: "%s", Domain: "%s". Errors: %s',
					$license,
					$token,
					$domain,
					implode( ', ', $response->get_error_messages() )
				) );
			}

			return false;
		}

		return $response['response']['code'] === WP_Http::OK;
	}

}