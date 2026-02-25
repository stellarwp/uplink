<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\REST;

use StellarWP\Uplink\Features\Manager;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * WP REST API controller for toggling features on and off.
 *
 * Restricted to logged-in Administrators (manage_options capability).
 *
 * @since TBD
 */
class Toggle_Controller extends WP_REST_Controller {

	/**
	 * The REST API namespace.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $namespace = 'stellarwp/uplink/v1';

	/**
	 * The REST API route base.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $rest_base = 'features';

	/**
	 * The feature manager instance.
	 *
	 * @since TBD
	 *
	 * @var Manager
	 */
	private Manager $manager;

	/**
	 * Constructor for the feature toggle REST API controller.
	 *
	 * @since TBD
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
	 * @since TBD
	 *
	 * @return void
	 */
	public function register_routes(): void {
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
	 * @since TBD
	 *
	 * @return bool
	 */
	public function check_permissions(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Enables a feature.
	 *
	 * @since TBD
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function enable( WP_REST_Request $request ) {
		$slug   = $request->get_param( 'slug' );
		$result = $this->manager->enable( $slug );

		// TODO: Not sure how we want to handle errors here, doing it like this for now.
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( [
			'slug'    => $slug,
			'enabled' => true,
		] );
	}

	/**
	 * Disables a feature.
	 *
	 * @since TBD
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function disable( WP_REST_Request $request ) {
		$slug   = $request->get_param( 'slug' );
		$result = $this->manager->disable( $slug );

		// TODO: Not sure how we want to handle errors here, doing it like this for now.
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( [
			'slug'    => $slug,
			'enabled' => false,
		] );
	}

	/**
	 * Gets the schema for a single feature toggle response.
	 *
	 * @since TBD
	 *
	 * @return array<string, mixed>
	 */
	public function get_item_schema(): array {
		if ( $this->schema ) {
			return $this->add_additional_fields_schema( $this->schema );
		}

		$this->schema = [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'feature-toggle',
			'type'       => 'object',
			'properties' => [
				'slug'    => [
					'description' => __( 'The feature slug.', '%TEXTDOMAIN%' ),
					'type'        => 'string',
					'readonly'    => true,
					'context'     => [ 'view' ],
				],
				'enabled' => [
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
	 * Gets the slug argument definition.
	 *
	 * @since TBD
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
