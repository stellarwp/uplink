<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Rest\V1;

use StellarWP\Uplink\Rest\Contracts\Authorized;
use StellarWP\Uplink\Rest\Rest_Controller;
use StellarWP\Uplink\Rest\Traits\With_Webhook_Authorization;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * A webhook endpoint to store a user's authorization token
 * provided from an Origin site.
 *
 * The Origin site must fire off the request once a token
 * is generated there.
 *
 * @route /wp-json/uplink/v1/webhooks
 */
final class Webhook_Controller extends Rest_Controller implements Authorized {

	use With_Webhook_Authorization;

	public const TOKEN = 'token';

	public function register_routes(): void {
		register_rest_route( $this->namespace, $this->route( 'receive-token' ), [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'store_token' ],
				'permission_callback' => [ $this, 'check_authorization' ],
				'args'                => [
					self::TOKEN => [
						'description' => esc_html__( 'The Authorization Token', 'prophecy' ),
						'type'        => 'string',
						'required'    => true,
					],
					// TODO: We may need to accept an origin slug here.
				],
				'show_in_index'       => false,
			],
		] );
	}

	/**
	 * @TODO Actually store the token and provide error checking.
	 *
	 * @param  WP_REST_Request  $request
	 *
	 * @return WP_REST_Response
	 */
	public function store_token( WP_REST_Request $request ): WP_REST_Response {
		return $this->success( [
			'message' => esc_html__( 'Token stored.', '%TEXTDOMAIN%' ),
		] );
	}

}
