<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Rest\V1;

use StellarWP\Uplink\Auth\Token\Token_Manager_Factory;
use StellarWP\Uplink\Rest\Contracts\Authorized;
use StellarWP\Uplink\Rest\Rest_Controller;
use StellarWP\Uplink\Rest\Traits\With_Webhook_Authorization;
use WP_Http;
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

	/**
	 * @var Token_Manager_Factory
	 */
	private $factory;

	public function __construct( string $namespace_base, string $version, string $base, Token_Manager_Factory $factory ) {
		parent::__construct( $namespace_base, $version, $base );

		$this->factory = $factory;
	}


	public function register_routes(): void {
		register_rest_route( $this->namespace, $this->route( 'receive-token' ), [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'store_token' ],
				'permission_callback' => [ $this, 'check_authorization' ],
				'args'                => [
					self::TOKEN => [
						'description'       => esc_html__( 'The Authorization Token', 'prophecy' ),
						'type'              => 'string',
						'required'          => true,
						'validate_callback' => [ $this->factory->make(), 'validate' ],
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
				'show_in_index'       => false,
			],
		] );
	}

	/**
	 * Store the newly created UUIDv4 token.
	 *
	 * @param  WP_REST_Request  $request
	 *
	 * @return WP_REST_Response
	 */
	public function store_token( WP_REST_Request $request ): WP_REST_Response {
		if ( $this->factory->make()->store( $request->get_param( self::TOKEN ) ) ) {
			return $this->success( [], WP_Http::CREATED, esc_html__( 'Token stored successfully.', '%TEXTDOMAIN%' ) );
		}

		return $this->error(
			esc_html__( 'Error storing token.', '%TEXTDOMAIN%' ),
			WP_Http::UNPROCESSABLE_ENTITY
		);
	}

}
