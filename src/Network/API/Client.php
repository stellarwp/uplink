<?php

namespace StellarWP\Network\API;

use StellarWP\Network\Container;

class Client {
	/**
	 * Base URL for the license key server.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public static $base_url = 'https://pue.theeventscalendar.com';

	/**
	 * API base endpoint.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public static $api_endpoint = '/api/plugins/v1/';

	/**
	 * Container.
	 *
	 * @since 1.0.0
	 *
	 * @var \StellarWP\Network\Container
	 */
	protected $container;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param Container $container Container.
	 */
	public function __construct( \tad_DI52_Container $container = null ) {
		$this->container = $container ?: Container::init();

		if ( defined( 'STELLAR_NETWORK_API_BASE_URL' ) && STELLAR_NETWORK_API_BASE_URL ) {
			static::$base_url = STELLAR_NETWORK_API_BASE_URL;
		}
	}

	/**
	 * Build hash.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $args Arguments to hash.
	 *
	 * @return string
	 */
	public function build_hash( $args ) {
		$args = array_filter( $args );

		ksort( $args );

		$args = json_encode( $args );

		return hash( 'sha256', $args );
	}

	/**
	 * GET request.
	 *
	 * @since 1.0.0
	 *
	 * @param string $endpoint
	 * @param array<mixed> $args
	 * @return mixed
	 */
	protected function get( $endpoint, $args ) {
		return $this->request( 'GET', $endpoint, $args );
	}

	/**
	 * POST request.
	 *
	 * @since 1.0.0
	 *
	 * @param string $endpoint
	 * @param array<mixed> $args
	 * @return mixed
	 */
	protected function post( $endpoint, $args ) {
		return $this->request( 'POST', $endpoint, $args );
	}

	/**
	 * Send a request to the StellarWP Network API.
	 *
	 * @since 1.0.0
	 *
	 * @param string $method
	 * @param string $endpoint
	 * @param array<mixed> $args
	 *
	 * @return mixed
	 */
	protected function request( $method, $endpoint, $args ) {
		$request_args = [
			'method'  => strtoupper( $method ),
			'headers' => [
				'Content-Type' => 'application/json',
			],
			'body'    => wp_json_encode( $args ),
			'timeout' => 15, // Seconds.
		];

		/**
		 * Filter the request arguments.
		 *
		 * @since 1.0.0
		 *
		 * @param array<mixed> $request_args Request arguments.
		 * @param string $endpoint Request method.
		 * @param array<mixed> $args Request data.
		 */
		$request_args = apply_filters( 'stellar_network_api_request_args', $request_args, $endpoint, $args );

		$url = static::$base_url . $endpoint;

		$response      = wp_remote_get( $url, $request_args );
		$response_body = wp_remote_retrieve_body( $response );
		$result        = json_decode( $response_body, true );

		/**
		 * Filter the API response.
		 *
		 * @since 1.0.0
		 *
		 * @param array $result API response.
		 * @param string $endpoint API endpoint.
		 * @param array $args API arguments.
		 */
		$result = apply_filters( 'stellar_network_api_response', $result, $endpoint, $args );

		return $result;
	}

	/**
	 * Validates the license.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args License validation arguments.
	 *
	 * @return array<mixed>
	 */
	public function validate_license( $args = [], $force = false ) {
		$results      = [];
		$request_hash = $this->build_hash( $args );
		$cache_key    = 'stellar_network_validate_license_' . $request_hash;

		if ( ! $force && $results = $this->container->getVar( $cache_key ) ) {
			return $results;
		}

		$results = $this->post( 'license/validate', $args );

		$this->container->setVar( $cache_key, $results );

		return $results;
	}
}