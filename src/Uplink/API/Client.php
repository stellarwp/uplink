<?php

namespace StellarWP\Uplink\API;

use StellarWP\ContainerContract\ContainerInterface;
use StellarWP\Uplink\Config;
use StellarWP\Uplink\Resources\Resource;
use StellarWP\Uplink\Site\Data;

/**
 * API Client class.
 *
 * @since 1.0.0
 *
 * @property-read string    $api_root  The API root path.
 * @property-read string    $base_url  The service base URL.
 * @property-read ContainerInterface $container Container instance.
 */
class Client {
	/**
	 * API base endpoint.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public static $api_root = '/api/plugins/v1/';

	/**
	 * Base URL for the license key server.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public static $base_url = 'https://pue.theeventscalendar.com';

	/**
	 * Container.
	 *
	 * @since 1.0.0
	 *
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param ContainerInterface $container Container.
	 */
	public function __construct( ContainerInterface $container = null ) {
		$this->container = $container ?: Config::get_container();

		if ( defined( 'STELLAR_UPLINK_API_BASE_URL' ) && STELLAR_UPLINK_API_BASE_URL ) {
			static::$base_url = preg_replace( '!/$!', '', STELLAR_UPLINK_API_BASE_URL );
		}

		if ( defined( 'STELLAR_UPLINK_API_ROOT' ) && STELLAR_UPLINK_API_ROOT ) {
			static::$api_root = trailingslashit( STELLAR_UPLINK_API_ROOT );
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

		if ( ! $args ) {
			return '';
		}

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
	 * Get API base URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_api_base_url(): string {
		/**
		 * Filter the API base URL.
		 *
		 * @since 1.0.0
		 *
		 * @param string $base_url Base URL.
		 */
		return apply_filters( 'stellar_uplink_' . Config::get_hook_prefix(). 'api_get_base_url', static::$base_url );
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
	 * Send a request to the StellarWP Uplink API.
	 *
	 * @since 1.0.0
	 *
	 * @param string $method
	 * @param string $endpoint
	 * @param array<mixed> $args
	 *
	 * @return \stdClass|null
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
		$request_args = apply_filters( 'stellar_uplink_' . Config::get_hook_prefix(). 'api_request_args', $request_args, $endpoint, $args );

		$url = static::$base_url . static::$api_root . $endpoint;

		$response      = wp_remote_get( $url, $request_args );
		$response_body = wp_remote_retrieve_body( $response );
		$result        = json_decode( $response_body );

		/**
		 * Filter the API response.
		 *
		 * @since 1.0.0
		 *
		 * @param \stdClass|null $result API response.
		 * @param string $endpoint API endpoint.
		 * @param array<mixed> $args API arguments.
		 */
		$result = apply_filters( 'stellar_uplink_' . Config::get_hook_prefix(). 'api_response', $result, $endpoint, $args );

		return $result;
	}

	/**
	 * Validates the license.
	 *
	 * @since 1.0.0
	 *
	 * @param Resource    $resource        Resource to validate.
	 * @param string|null $key             License key.
	 * @param string      $validation_type Validation type (local or network).
	 * @param bool        $force           Force the validation.
	 *
	 * @return mixed
	 */
	public function validate_license( Resource $resource, string $key = null, string $validation_type = 'local', bool $force = false ) {
		$results = [];

		/** @var Data */
		$site_data = $this->container->get( Data::class );
		$args      = $resource->get_validation_args();

		if ( ! empty( $key ) ) {
			$args['key'] = sanitize_text_field( $key );
		}

		$args['domain'] = $site_data->get_domain();
		$args['stats']  = $site_data->get_stats();

		$args['stats']['network']['network_activated'] = $resource->is_network_activated();

		/**
		 * Filter the license validation arguments.
		 *
		 * @since 1.0.0
		 *
		 * @param array<mixed> $args License validation arguments.
		 */
		$args = apply_filters( 'stellar_uplink_' . Config::get_hook_prefix(). 'client_validate_license_args', $args );

		$request_hash = $this->build_hash( $args );
		$cache_key    = 'stellar_uplink_validate_license_' . $request_hash;

		$results = $this->container->getVar( $cache_key );

		if ( $force || ! $results ) {

			$results = $this->post( 'license/validate', $args );

			$this->container->setVar( $cache_key, $results );
		}

		$results = new Validation_Response( $key, $validation_type, $results, $resource );

		/**
		 * Filter the license validation results.
		 *
		 * @since 1.0.0
		 *
		 * @param Validation_Response $results License validation results.
		 * @param array<mixed> $args License validation arguments.
		 */
		return apply_filters( 'stellar_uplink_' . Config::get_hook_prefix(). 'client_validate_license', $results, $args );
	}
}
