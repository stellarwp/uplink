<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Rest\Contracts;

use WP_REST_Request;

interface Authorized {

	public const NONCE_HEADER = 'X-Uplink-Nonce';

	/**
	 * Used for the `permission_callback` callback to determine if a request is
	 * authorized to proceed.
	 *
	 * @param  WP_REST_Request  $request
	 *
	 * @return bool Whether to allow the request to continue
	 */
	public function check_authorization( WP_REST_Request $request ): bool;

}
