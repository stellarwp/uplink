<?php declare( strict_types=1 );

namespace StellarWP\Uplink\API\REST\V1;

use StellarWP\Uplink\Utils\License_Key;
use StellarWP\Uplink\Licensing\License_Manager;
use StellarWP\Uplink\Licensing\Product_Collection;
use StellarWP\Uplink\Site\Data;
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
	 * The site data provider.
	 *
	 * @since 3.0.0
	 *
	 * @var Data
	 */
	private Data $site_data;

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 *
	 * @param License_Manager $manager   The license manager.
	 * @param Data            $site_data The site data provider.
	 *
	 * @return void
	 */
	public function __construct( License_Manager $manager, Data $site_data ) {
		$this->manager   = $manager;
		$this->site_data = $site_data;
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

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<key>[A-Za-z0-9-]+)',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'lookup_item' ],
					'permission_callback' => [ $this, 'check_permissions' ],
					'args'                => [
						'key' => [
							'description'       => __( 'The license key to look up.', '%TEXTDOMAIN%' ),
							'type'              => 'string',
							'required'          => true,
							'validate_callback' => static function ( $value ): bool {
								return is_string( $value ) && License_Key::is_valid_format( $value );
							},
						],
					],
				],
				'schema' => [ $this, 'get_public_item_schema' ],
			]
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/validate',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'validate_item' ],
					'permission_callback' => [ $this, 'check_permissions' ],
					'args'                => $this->get_validate_args(),
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
	 * Returns the current unified license key and its associated products.
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
		$domain   = $this->site_data->get_domain();
		$key      = $this->manager->get_key();
		$products = $this->manager->get_products( $domain );

		if ( is_wp_error( $products ) ) {
			$products = new Product_Collection();
		}

		return new WP_REST_Response(
			License_Response::make( $key, $products )
		);
	}

	/**
	 * Looks up the products for a license key, skipping storage.
	 *
	 * @since 3.0.0
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function lookup_item( $request ) {
		/** @var string $key */
		$key    = $request->get_param( 'key' );
		$domain = $this->site_data->get_domain();
		$result = $this->manager->lookup_products( $key, $domain );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response(
			License_Response::make( $key, $result )
		);
	}

	/**
	 * Validates a license key against the remote API and stores it.
	 *
	 * Verifies the key is recognized (has products) but does not activate
	 * any product or consume a seat. Returns the stored key on success.
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
		$domain  = $this->site_data->get_domain();

		$result = $this->manager->validate_and_store( $key, $domain, $network );

		if ( is_wp_error( $result ) ) {
			$data = $result->get_error_data();

			if ( ! is_array( $data ) || empty( $data['status'] ) ) {
				$result->add_data( [ 'status' => 500 ] );
			}

			return $result;
		}

		$products = $this->manager->get_products( $domain );

		if ( is_wp_error( $products ) ) {
			$products = new Product_Collection();
		}

		return new WP_REST_Response(
			License_Response::make( $this->manager->get_key(), $products )
		);
	}

	/**
	 * Validates a product on this domain using the stored license key.
	 *
	 * Calls the licensing API validate endpoint, which may consume an
	 * activation seat. Returns the validation result.
	 *
	 * @since 3.0.0
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function validate_item( $request ) {
		/** @var string $product_slug */
		$product_slug = $request->get_param( 'product_slug' );
		$domain       = $this->site_data->get_domain();

		$result = $this->manager->validate_product( $domain, $product_slug );

		if ( is_wp_error( $result ) ) {
			$data = $result->get_error_data();

			if ( ! is_array( $data ) || empty( $data['status'] ) ) {
				$result->add_data( [ 'status' => 500 ] );
			}

			return $result;
		}

		// Product validation fetched the updated products list.
		$products = $this->manager->get_products( $domain );

		if ( is_wp_error( $products ) ) {
			$products = new Product_Collection();
		}

		return new WP_REST_Response(
			License_Response::make( $this->manager->get_key(), $products )
		);
	}

	/**
	 * Deletes the stored unified license key.
	 *
	 * This only removes the locally stored key. It does not free any
	 * activation seats on the licensing service.
	 *
	 * @since 3.0.0
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response
	 */
	public function delete_item( $request ): WP_REST_Response {
		$network = (bool) $request->get_param( 'network' );

		$this->manager->delete_key( $network );

		return new WP_REST_Response( null, 204 );
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
				'key'      => [
					'description' => __( 'The unified license key.', '%TEXTDOMAIN%' ),
					'type'        => [ 'string', 'null' ],
					'context'     => [ 'view' ],
				],
				'products' => [
					'description' => __( 'The products associated with the license key.', '%TEXTDOMAIN%' ),
					'type'        => 'array',
					'context'     => [ 'view' ],
					'items'       => [
						'type'       => 'object',
						'properties' => [
							'product_slug'      => [
								'description' => __( 'The product identifier.', '%TEXTDOMAIN%' ),
								'type'        => 'string',
							],
							'tier'              => [
								'description' => __( 'The subscription tier.', '%TEXTDOMAIN%' ),
								'type'        => 'string',
							],
							'pending_tier'      => [
								'description' => __( 'The pending tier on next renewal.', '%TEXTDOMAIN%' ),
								'type'        => [ 'string', 'null' ],
							],
							'status'            => [
								'description' => __( 'The subscription status.', '%TEXTDOMAIN%' ),
								'type'        => 'string',
							],
							'expires'           => [
								'description' => __( 'The expiration date.', '%TEXTDOMAIN%' ),
								'type'        => 'string',
								'format'      => 'date-time',
							],
							'activations'       => [
								'description' => __( 'Activation seat data.', '%TEXTDOMAIN%' ),
								'type'        => 'object',
								'properties'  => [
									'site_limit'   => [
										'description' => __( 'Maximum activation seats (0 = unlimited).', '%TEXTDOMAIN%' ),
										'type'        => 'integer',
									],
									'active_count' => [
										'description' => __( 'Current active activations.', '%TEXTDOMAIN%' ),
										'type'        => 'integer',
									],
									'over_limit'   => [
										'description' => __( 'Whether the seat limit is exceeded.', '%TEXTDOMAIN%' ),
										'type'        => 'boolean',
									],
								],
							],
							'installed_here'    => [
								'description' => __( 'Whether the product is activated on this domain.', '%TEXTDOMAIN%' ),
								'type'        => [ 'boolean', 'null' ],
							],
							'validation_status' => [
								'description' => __( 'The validation status for this product.', '%TEXTDOMAIN%' ),
								'type'        => [ 'string', 'null' ],
							],
							'is_valid'          => [
								'description' => __( 'Whether the product has a valid license.', '%TEXTDOMAIN%' ),
								'type'        => 'boolean',
							],
						],
					],
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
	 * Gets the argument definitions for the validate (POST) endpoint.
	 *
	 * @since 3.0.0
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function get_validate_args(): array {
		return [
			'product_slug' => [
				'description'       => __( 'The product to validate.', '%TEXTDOMAIN%' ),
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			],
		];
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
