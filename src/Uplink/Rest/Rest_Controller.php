<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Rest;

use WP_Http;
use WP_REST_Controller;
use WP_REST_Response;

class Rest_Controller extends WP_REST_Controller {

	/**
	 * The namespace base, e.g. "uplink"
	 *
	 * @var string
	 */
	protected $namespace_base;

	/**
	 * The API version, e.g. "1"
	 *
	 * @var string
	 */
	protected $version;

	/**
	 * The child controller's base path, e.g. "webhooks"
	 *
	 * @var string
	 */
	protected $base;

	public function __construct(
		string $namespace_base,
		string $version,
		string $base
	) {
		$this->base           = $base;
		$this->version        = $version;
		$this->namespace_base = $namespace_base;
		$this->namespace      = $this->get_namespace();
		$this->rest_base      = $this->base;
	}

	public function get_base_url(): string {
		return rest_url() . $this->namespace . '/' . $this->rest_base;
	}

	public function get_relative_route(): string {
		return sprintf( '/%s/%s/', $this->get_namespace(), $this->rest_base );
	}

	protected function route( string $path ): string {
		return sprintf( '/%s/%s', $this->rest_base, $path );
	}

	protected function get_namespace(): string {
		return $this->namespace_base . '/v' . $this->version;
	}

	/**
	 * Generate a success response.
	 *
	 * @param  mixed  $data The data to attach to the response.
	 * @param  int  $status The HTTP status code, should be in the 200-300 range.
	 * @param  string  $message An optional message to include.
	 *
	 * @return WP_REST_Response
	 */
	protected function success( $data = [], int $status = WP_Http::OK, string $message = '' ): WP_REST_Response {
		return new WP_REST_Response( array_filter( [
			'status'  => $status,
			'message' => $message,
			'data'    => $data,
		] ), $status );
	}

	/**
	 * Generate an error response.
	 *
	 * @param  string  $message  The message to display for the detail property.
	 * @param  int  $status  The HTTP status code, should be in the 400+ range.
	 */
	protected function error( string $message = '', int $status = WP_Http::INTERNAL_SERVER_ERROR ): WP_REST_Response {
		return new WP_REST_Response( [
			'status' => $status,
			'error'  => $message,
		], $status );
	}

}
