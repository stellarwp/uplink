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
	 * @param mixed $data
	 * @param  int  $code
	 *
	 * @return WP_REST_Response
	 */
	protected function success( $data, int $code = 200 ): WP_REST_Response {
		return new WP_REST_Response( [
			'data' => $data,
		], $code );
	}

	/**
	 * Generate a RFC 7807 API error response (API Problem).
	 *
	 * @param  int  $status  The HTTP status code, should be in the 400+ range.
	 * @param  string  $message  The message to display for the detail property.
	 */
	protected function error( string $message = '', int $status = WP_Http::INTERNAL_SERVER_ERROR ): WP_REST_Response {
		return new WP_REST_Response( [
			'status' => $status,
			'error'  => $message,
		], $status );
	}

}
