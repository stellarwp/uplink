<?php declare( strict_types=1 );

namespace StellarWP\Uplink\API\REST\V1;

use StellarWP\Uplink\Licensing\Error_Code;
use StellarWP\Uplink\Utils\License_Key;
use StellarWP\Uplink\Licensing\License_Manager;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * WP REST API controller for the unified license key.
 *
 * Provides GET, POST, and DELETE endpoints for reading,
 * storing, and removing the unified license key.
 *
 * All endpoints require the manage_options capability.
 *
 * @since 3.0.0
 */
final class License_Controller extends WP_REST_Controller {

	/**
	 * The REST API namespace.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	protected $namespace = 'stellarwp/uplink/v1';

	/**
	 * The REST API route base.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	protected $rest_base = 'license';

	/**
	 * The license manager.
	 *
	 * @since 3.0.0
	 *
	 * @var License_Manager
	 */
	private License_Manager $manager;

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 *
	 * @param License_Manager $manager The license manager.
	 *
	 * @return void
	 */
	public function __construct( License_Manager $manager ) {
		$this->manager = $manager;
	}

	/**
	 * Registers the routes.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_item' ],
					'permission_callback' => [ $this, 'check_permissions' ],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'store_item' ],
					'permission_callback' => [ $this, 'check_permissions' ],
					'args'                => $this->get_store_args(),
				],
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_item' ],
					'permission_callback' => [ $this, 'check_permissions' ],
					'args'                => $this->get_network_args(),
				],
				'schema' => [ $this, 'get_public_item_schema' ],
			]
		);
	}

	/**
	 * Permission callback: require manage_options capability.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public function check_permissions(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Returns the current unified license key.
	 *
	 * Always returns 200. The key field will be null if no key is stored
	 * and none is discoverable from the product registry.
	 *
	 * @since 3.0.0
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response
	 */
	public function get_item( $request ): WP_REST_Response {
		return new WP_REST_Response( [ 'key' => $this->manager->get() ] );
	}

	/**
	 * Stores the unified license key.
	 *
	 * @since 3.0.0
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function store_item( $request ) {
		/** @var string $key */
		$key     = $request->get_param( 'key' );
		$network = (bool) $request->get_param( 'network' );

		// TODO: Validate the license key.
		// Context: John was moving fast, but if you could evolve the Products_Repository into something that might be more appropriate, like a resolver... (or just call Products_Repository::get for now), but what we should do here is grab the license from licensing and verify it's good before we store the key. And since this already maps to the fixture data, we have a list of difference licensing situations we can work with.

		if ( ! $this->manager->store( $key, $network ) ) {
			return new WP_Error(
				Error_Code::STORE_FAILED,
				__( 'The license key could not be stored.', '%TEXTDOMAIN%' ),
				[ 'status' => 500 ]
			);
		}

		return new WP_REST_Response( [ 'key' => $this->manager->get() ] );
	}

	/**
	 * Deletes the stored unified license key.
	 *
	 * @since 3.0.0
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response
	 */
	public function delete_item( $request ): WP_REST_Response {
		$network = (bool) $request->get_param( 'network' );

		$this->manager->delete( $network );

		return new WP_REST_Response( [ 'deleted' => true ] );
	}

	/**
	 * Gets the schema for the license resource.
	 *
	 * @since 3.0.0
	 *
	 * @return array<string, mixed>
	 */
	public function get_item_schema(): array {
		if ( $this->schema ) {
			/** @var array<string, mixed> */
			return $this->add_additional_fields_schema( $this->schema );
		}

		$this->schema = [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'license',
			'type'       => 'object',
			'properties' => [
				'key' => [
					'description' => __( 'The unified license key.', '%TEXTDOMAIN%' ),
					'type'        => [ 'string', 'null' ],
					'context'     => [ 'view' ],
				],
			],
		];

		/** @var array<string, mixed> */
		return $this->add_additional_fields_schema( $this->schema );
	}

	/**
	 * Gets the argument definitions for the store (POST) endpoint.
	 *
	 * @since 3.0.0
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function get_store_args(): array {
		return array_merge(
			[
				'key' => [
					'description'       => __( 'The license key to store.', '%TEXTDOMAIN%' ),
					'type'              => 'string',
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => static function ( $value ): bool {
						return is_string( $value ) && License_Key::is_valid_format( $value );
					},
				],
			],
			$this->get_network_args()
		);
	}

	/**
	 * Gets the shared network argument definition.
	 *
	 * @since 3.0.0
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function get_network_args(): array {
		return [
			'network' => [
				'description' => __( 'Whether to operate on the network-level key (multisite only).', '%TEXTDOMAIN%' ),
				'type'        => 'boolean',
				'default'     => false,
			],
		];
	}
}
