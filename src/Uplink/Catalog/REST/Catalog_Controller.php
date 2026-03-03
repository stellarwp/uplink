<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Catalog\REST;

use StellarWP\Uplink\Catalog\Catalog_Repository;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * WP REST API controller for reading the product catalog.
 *
 * @since 3.0.0
 */
final class Catalog_Controller extends WP_REST_Controller {

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
	protected $rest_base = 'catalog';

	/**
	 * The catalog repository.
	 *
	 * @since 3.0.0
	 *
	 * @var Catalog_Repository
	 */
	private Catalog_Repository $repository;

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 *
	 * @param Catalog_Repository $repository The catalog repository.
	 *
	 * @return void
	 */
	public function __construct( Catalog_Repository $repository ) {
		$this->repository = $repository;
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
					'callback'            => [ $this, 'get_items' ],
					'permission_callback' => [ $this, 'check_permissions' ],
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
	 * Returns all product catalogs.
	 *
	 * @since 3.0.0
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response|\WP_Error
	 */
	public function get_items( $request ) {
		$catalogs = $this->repository->get();

		if ( is_wp_error( $catalogs ) ) {
			return $catalogs;
		}

		$data = [];

		foreach ( $catalogs as $catalog ) {
			$data[] = $catalog->to_array();
		}

		return new WP_REST_Response( $data );
	}

	/**
	 * Gets the schema for a single catalog item.
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
			'title'      => 'catalog',
			'type'       => 'object',
			'properties' => [
				'product_slug' => [
					'description' => __( 'The product slug.', '%TEXTDOMAIN%' ),
					'type'        => 'string',
					'readonly'    => true,
					'context'     => [ 'view' ],
				],
				'tiers'        => [
					'description' => __( 'The product tiers ordered by rank.', '%TEXTDOMAIN%' ),
					'type'        => 'array',
					'readonly'    => true,
					'context'     => [ 'view' ],
					'items'       => [
						'type'       => 'object',
						'properties' => [
							'slug'         => [
								'type' => 'string',
							],
							'name'         => [
								'type' => 'string',
							],
							'rank'         => [
								'type' => 'integer',
							],
							'purchase_url' => [
								'type'   => 'string',
								'format' => 'uri',
							],
						],
					],
				],
				'features'     => [
					'description' => __( 'The product features.', '%TEXTDOMAIN%' ),
					'type'        => 'array',
					'readonly'    => true,
					'context'     => [ 'view' ],
					'items'       => [
						'type'       => 'object',
						'properties' => [
							'feature_slug' => [
								'type' => 'string',
							],
							'type'         => [
								'type' => 'string',
							],
							'minimum_tier' => [
								'type' => 'string',
							],
							'name'         => [
								'type' => 'string',
							],
							'description'  => [
								'type' => 'string',
							],
							'category'     => [
								'type' => 'string',
							],
						],
					],
				],
			],
		];

		/** @var array<string, mixed> */
		return $this->add_additional_fields_schema( $this->schema );
	}
}
