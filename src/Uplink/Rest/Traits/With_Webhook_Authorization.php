<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Rest\Traits;

use StellarWP\Uplink\Auth\Nonce;
use StellarWP\Uplink\Rest\Contracts\Authorized;
use StellarWP\Uplink\Rest\Rest_Controller;
use WP_REST_Request;

/**
 * @mixin Authorized
 * @mixin Rest_Controller
 */
trait With_Webhook_Authorization {

	/**
	 * Checks if the webhook nonce is valid and generated between 0-12 hours ago.
	 *
	 * @param  WP_REST_Request  $request
	 *
	 * @return bool
	 */
	public function check_authorization( WP_REST_Request $request ): bool {
		$nonce = $request->get_header( self::NONCE_HEADER );

		return wp_verify_nonce( $nonce, Nonce::NONCE_ACTION ) === 1;
	}

}
