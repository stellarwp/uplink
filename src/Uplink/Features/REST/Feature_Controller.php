<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\REST;

use StellarWP\Uplink\Features\Error_Code;
use StellarWP\Uplink\Features\Manager;
use StellarWP\Uplink\Features\Types\Feature;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * WP REST API controller for managing features.
 *
 * Supports listing, retrieving, enabling, and disabling features.
 * Restricted to logged-in Administrators (manage_options capability).
 *
 * @since 3.0.0
 */
class Feature_Controller extends WP_REST_Controller {

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
	protected $rest_base = 'features';

	/**
	 * The feature manager instance.
	 *
	 * @since 3.0.0
	 *
	 * @var Manager
	 */
	private Manager $manager;

	/**
	 * Constructor for the feature REST API controller.
	 *
	 * @since 3.0.0
	 *
	 * @param Manager $manager The feature manager.
	 *
	 * @return void
	 */
	public function __construct( Manager $manager ) {
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
		register_rest_route( $this->namespace, '/' . $this->rest_base, [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_items' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'args'                => $this->get_collection_params(),
			],
			'schema' => [ $this, 'get_public_item_schema' ],
		] );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<slug>[a-zA-Z0-9_-]+)', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_item' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'args'                => [
					'slug' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			],
			'schema' => [ $this, 'get_public_item_schema' ],
		] );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<slug>[a-zA-Z0-9_-]+)/enable', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'enable' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'args'                => $this->get_slug_args(),
			],
			'schema' => [ $this, 'get_public_item_schema' ],
		] );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<slug>[a-zA-Z0-9_-]+)/disable', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'disable' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'args'                => $this->get_slug_args(),
			],
			'schema' => [ $this, 'get_public_item_schema' ],
		] );
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
	 * Lists features with optional filters.
	 *
	 * @since 3.0.0
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_items( $request ) {
		$features = $this->manager->get_features();

		if ( is_wp_error( $features ) ) {
			return $features;
		}

		$group     = $request->get_param( 'group' );
		$tier      = $request->get_param( 'tier' );
		$available = $request->get_param( 'available' );
		$type      = $request->get_param( 'type' );

		if ( $group !== null || $tier !== null || $available !== null || $type !== null ) {
			$features = $features->filter( $group, $tier, $available, $type );
		}

		$data = [];

		foreach ( $features as $feature ) {
			$data[] = $this->prepare_feature_data( $feature );
		}

		return new WP_REST_Response( $data );
	}

	/**
	 * Retrieves a single feature by slug.
	 *
	 * @since 3.0.0
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {
		$slug     = $request->get_param( 'slug' );
		$features = $this->manager->get_features();

		if ( is_wp_error( $features ) ) {
			return $features;
		}

		$feature = $features->get( $slug );

		if ( ! $feature ) {
			return new WP_Error(
				Error_Code::FEATURE_NOT_FOUND,
				sprintf( 'Feature "%s" not found.', $slug ),
				[ 'status' => 404 ]
			);
		}

		return new WP_REST_Response( $this->prepare_feature_data( $feature ) );
	}

	/**
	 * Enables a feature.
	 *
	 * @since 3.0.0
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function enable( WP_REST_Request $request ) {
		$slug    = $request->get_param( 'slug' );
		$feature = $this->manager->get_feature( $slug );

		if ( ! $feature ) {
			return new WP_Error(
				Error_Code::FEATURE_NOT_FOUND,
				sprintf( 'Feature "%s" not found.', $slug ),
				[ 'status' => 404 ]
			);
		}

		$result = $this->manager->enable( $slug );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( $feature->to_array() + [
			'enabled' => true,
		] );
	}

	/**
	 * Disables a feature.
	 *
	 * @since 3.0.0
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function disable( WP_REST_Request $request ) {
		$slug    = $request->get_param( 'slug' );
		$feature = $this->manager->get_feature( $slug );

		if ( ! $feature ) {
			return new WP_Error(
				Error_Code::FEATURE_NOT_FOUND,
				sprintf( 'Feature "%s" not found.', $slug ),
				[ 'status' => 404 ]
			);
		}

		$result = $this->manager->disable( $slug );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( $feature->to_array() + [
			'enabled' => false,
		] );
	}

	/**
	 * Gets the schema for a single feature response.
	 *
	 * @since 3.0.0
	 *
	 * @return array<string, mixed>
	 */
	public function get_item_schema(): array {
		if ( $this->schema ) {
			return $this->add_additional_fields_schema( $this->schema );
		}

		$this->schema = [
			'$schema'              => 'http://json-schema.org/draft-04/schema#',
			'title'                => 'feature',
			'type'                 => 'object',
			'additionalProperties' => true,
			'properties'           => [
				'slug'          => [
					'description' => __( 'The feature slug.', '%TEXTDOMAIN%' ),
					'type'        => 'string',
					'readonly'    => true,
					'context'     => [ 'view' ],
				],
				'name'          => [
					'description' => __( 'The feature display name.', '%TEXTDOMAIN%' ),
					'type'        => 'string',
					'readonly'    => true,
					'context'     => [ 'view' ],
				],
				'description'   => [
					'description' => __( 'The feature description.', '%TEXTDOMAIN%' ),
					'type'        => 'string',
					'readonly'    => true,
					'context'     => [ 'view' ],
				],
				'group'         => [
					'description' => __( 'The product group the feature belongs to.', '%TEXTDOMAIN%' ),
					'type'        => 'string',
					'readonly'    => true,
					'context'     => [ 'view' ],
				],
				'tier'          => [
					'description' => __( 'The feature tier.', '%TEXTDOMAIN%' ),
					'type'        => 'string',
					'readonly'    => true,
					'context'     => [ 'view' ],
				],
				'type'          => [
					'description' => __( 'The feature type identifier.', '%TEXTDOMAIN%' ),
					'type'        => 'string',
					'readonly'    => true,
					'context'     => [ 'view' ],
				],
				'is_available'  => [
					'description' => __( 'Whether the feature is available for the current site.', '%TEXTDOMAIN%' ),
					'type'        => 'boolean',
					'readonly'    => true,
					'context'     => [ 'view' ],
				],
				'documentation' => [
					'description' => __( 'The URL to the feature documentation.', '%TEXTDOMAIN%' ),
					'type'        => 'string',
					'format'      => 'uri',
					'readonly'    => true,
					'context'     => [ 'view' ],
				],
				'enabled'       => [
					'description' => __( 'Whether the feature is currently enabled.', '%TEXTDOMAIN%' ),
					'type'        => 'boolean',
					'readonly'    => true,
					'context'     => [ 'view' ],
				],
			],
		];

		return $this->add_additional_fields_schema( $this->schema );
	}

	/**
	 * Gets the query parameters for the features collection.
	 *
	 * @since 3.0.0
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_collection_params(): array {
		return [
			'group'     => [
				'description'       => __( 'Filter by product group.', '%TEXTDOMAIN%' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			],
			'tier'      => [
				'description'       => __( 'Filter by feature tier.', '%TEXTDOMAIN%' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			],
			'available' => [
				'description' => __( 'Filter by availability.', '%TEXTDOMAIN%' ),
				'type'        => 'boolean',
			],
			'type'      => [
				'description'       => __( 'Filter by feature type.', '%TEXTDOMAIN%' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			],
		];
	}

	/**
	 * Prepares feature data for the response.
	 *
	 * @since 3.0.0
	 *
	 * @param Feature $feature The feature instance.
	 *
	 * @return array<string, mixed>
	 */
	private function prepare_feature_data( Feature $feature ): array {
		return $feature->to_array() + [
			'enabled' => $this->manager->is_enabled( $feature->get_slug() ),
		];
	}

	/**
	 * Gets the slug argument definition for toggle endpoints.
	 *
	 * @since 3.0.0
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function get_slug_args(): array {
		return [
			'slug' => [
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => function ( $slug ) {
					return $this->manager->is_available( $slug );
				},
			],
		];
	}
}
