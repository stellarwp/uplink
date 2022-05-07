<?php

namespace StellarWP\Network\API;

use StellarWP\Network\Container;

/**
 * API Client class.
 *
 * @since 1.0.0
 *
 * @property-read string    $base_url  The service base URL.
 * @property-read string    $api_root  The API root path.
 * @property-read Container $container Container instance.
 */
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
	public static $api_root = '/api/plugins/v1/';

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
			static::$base_url = preg_replace( '!/$!', '', STELLAR_NETWORK_API_BASE_URL );
		}

		if ( defined( 'STELLAR_NETWORK_API_ROOT' ) && STELLAR_NETWORK_API_ROOT ) {
			static::$api_root = trailingslashit( STELLAR_NETWORK_API_ROOT );
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

		$url = static::$base_url . static::$api_root . $endpoint;

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
	 * @param array $args {
	 *     License validation arguments.
	 *
	 *     @type string $key     License key.
	 *     @type string $plugin  Plugin slug.
	 *     @type array  $stats   Array of stats.
	 *     @type string $version Optional. Plugin version.
	 * }
	 *
	 * @return array<mixed>
	 */
	public function validate_license( $args = [], $force = false ) {
		$results      = [];
		$request_hash = $this->build_hash( $args );
		$cache_key    = 'stellar_network_validate_license_' . $request_hash;

		$results = $this->container->getVar( $cache_key );

		if ( $force || ! $results ) {
			$site_data      = $this->container->make( Data::class );
			$args['domain'] = $site_data->get_domain();
			$args['stats']  = $site_data->get_stats();

			/**
			 * Filter the license validation arguments.
			 *
			 * @since 1.0.0
			 *
			 * @param array<mixed> $args License validation arguments.
			 */
			$args = apply_filters( 'stellar_network_client_validate_license_args', $args );

			$results = $this->post( 'license/validate', $args );

			$this->container->setVar( $cache_key, $results );
		}

		/**
		 * Filter the license validation results.
		 *
		 * @since 1.0.0
		 *
		 * @param array<mixed> $results License validation results.
		 * @param array<mixed> $args License validation arguments.
		 */
		return apply_filters( 'stellar_network_client_validate_license', $results, $args );
	}
}