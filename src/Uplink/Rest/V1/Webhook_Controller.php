<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Rest\V1;

use StellarWP\Uplink\Auth\Token\Contracts\Token_Manager;
use StellarWP\Uplink\Resources\Collection;
use StellarWP\Uplink\Rest\Contracts\Authorized;
use StellarWP\Uplink\Rest\Rest_Controller;
use StellarWP\Uplink\Rest\Traits\With_Webhook_Authorization;
use StellarWP\Uplink\Utils\Sanitize;
use WP_Error;
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

	public const TOKEN   = 'token';
	public const LICENSE = 'license';
	public const SLUG    = 'slug';

	/**
	 * @var Token_Manager
	 */
	private $token_manager;

	/**
	 * @var Collection
	 */
	private $collection;

	public function __construct(
		string $namespace_base,
		string $version,
		string $base,
		Token_Manager $token_manager,
		Collection $collection
	) {
		parent::__construct( $namespace_base, $version, $base );

		$this->token_manager = $token_manager;
		$this->collection    = $collection;
	}


	public function register_routes(): void {
		register_rest_route( $this->namespace, $this->route( 'receive-auth' ), [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'store_auth' ],
				'permission_callback' => [ $this, 'check_authorization' ],
				'args'                => [
					self::TOKEN   => [
						'description'       => esc_html__( 'The Authorization Token', '%TEXTDOMAIN%' ),
						'type'              => 'string',
						'required'          => true,
						'validate_callback' => [ $this->token_manager, 'validate' ],
						'sanitize_callback' => 'sanitize_text_field',
					],
					self::LICENSE => [
						'description'       => esc_html__( 'An optional License Key to store', '%TEXTDOMAIN%' ),
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => [ Sanitize::class, 'key' ],
						'validate_callback' => static function ( $param, WP_REST_Request $request ) {
							if ( $request->get_param( self::SLUG ) ) {
								return true;
							}

							return new WP_Error(
								'rest_invalid_params',
								__( 'A license must also have a slug.', '%TEXTDOMAIN%' ),
								[ 'status' => WP_Http::UNPROCESSABLE_ENTITY ]
							);
						},
					],
					self::SLUG    => [
						'description'       => esc_html__( 'An optional Plugin or Service Slug associated with the license key', '%TEXTDOMAIN%' ),
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_title',
						'validate_callback' => function ( $param, WP_REST_Request $request ) {
							if ( ! $request->get_param( self::LICENSE ) ) {
								return new WP_Error(
									'rest_invalid_params',
									__( 'A slug must also have a license.', '%TEXTDOMAIN%' ),
									[ 'status' => WP_Http::UNPROCESSABLE_ENTITY ]
								);
							}

							if ( ! $this->collection->offsetExists( (string) $param ) ) {
								return new WP_Error(
									'rest_invalid_params',
									__( 'Plugin or Service slug not found.', '%TEXTDOMAIN%' ),
									[ 'status' => WP_Http::UNPROCESSABLE_ENTITY ]
								);
							}

							return true;
						},
					],
				],
				'show_in_index'       => false,
			],
		] );
	}

	/**
	 * Store the newly created UUIDv4 token and an optional License Key.
	 *
	 * @param  WP_REST_Request  $request
	 *
	 * @return WP_REST_Response
	 */
	public function store_auth( WP_REST_Request $request ): WP_REST_Response {
		$token   = $request->get_param( self::TOKEN );
		$license = $request->get_param( self::LICENSE );
		$slug    = $request->get_param( self::SLUG );

		if ( ! $this->token_manager->store( $token ) ) {
			return $this->error(
				esc_html__( 'Error storing token.', '%TEXTDOMAIN%' ),
				WP_Http::UNPROCESSABLE_ENTITY
			);
		}

		// Store or override an existing license.
		if ( $license && $slug ) {
			if ( ! $this->collection->offsetExists( $slug ) ) {
				return $this->error(
					esc_html__( 'Plugin or Service slug not found.', '%TEXTDOMAIN%' ),
					WP_Http::UNPROCESSABLE_ENTITY
				);
			}

			$plugin = $this->collection->offsetGet( $slug );

			if ( ! $plugin->set_license_key( $license, 'network' ) ) {
				return $this->error(
					esc_html__( 'Error storing license key.', '%TEXTDOMAIN%' ),
					WP_Http::UNPROCESSABLE_ENTITY
				);
			}
		}

		return $this->success(
			[],
			WP_Http::CREATED,
			esc_html__( 'Stored successfully.', '%TEXTDOMAIN%' )
		);
	}

}
