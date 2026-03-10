<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Traits;

use StellarWP\Uplink\Licensing\Http_Client as Licensing_Http_Client;
use StellarWP\Uplink\Catalog\Http_Client as Catalog_Http_Client;

/**
 * Trait for tests that need HTTP clients backed by fixture data.
 *
 * Hooks into `pre_http_request` to intercept outgoing requests to the
 * licensing and catalog API endpoints and serve local fixture JSON.
 */
trait With_Fixture_Http {

	/**
	 * The base URL used by HTTP clients during tests.
	 *
	 * @var string
	 */
	private static string $fixture_base_url = 'https://licensing-test.stellarwp.com';

	/**
	 * Create a Licensing Http_Client pointing at the fixture base URL.
	 *
	 * @return Licensing_Http_Client
	 */
	protected function make_licensing_http_client(): Licensing_Http_Client {
		return new Licensing_Http_Client( self::$fixture_base_url );
	}

	/**
	 * Create a Catalog Http_Client pointing at the fixture base URL.
	 *
	 * @param string|null $key Optional license key for key-scoped catalog lookups.
	 *
	 * @return Catalog_Http_Client
	 */
	protected function make_catalog_http_client( ?string $key = null ): Catalog_Http_Client {
		return new Catalog_Http_Client( self::$fixture_base_url, $key );
	}

	/**
	 * Register the `pre_http_request` filter that intercepts requests
	 * to the fixture base URL and serves local JSON.
	 *
	 * Call this in setUp() before making any HTTP calls.
	 *
	 * @return void
	 */
	protected function register_fixture_http_interceptor(): void {
		add_filter( 'pre_http_request', [ $this, 'intercept_fixture_http' ], 10, 3 );
	}

	/**
	 * Remove the `pre_http_request` filter.
	 *
	 * Call this in tearDown().
	 *
	 * @return void
	 */
	protected function unregister_fixture_http_interceptor(): void {
		remove_filter( 'pre_http_request', [ $this, 'intercept_fixture_http' ], 10 );
	}

	/**
	 * Intercept HTTP requests to the fixture base URL.
	 *
	 * @param false|array $response    Preemptive return value.
	 * @param array       $parsed_args Request arguments.
	 * @param string      $url         Request URL.
	 *
	 * @return false|array
	 */
	public function intercept_fixture_http( $response, $parsed_args, $url ) {
		if ( strpos( $url, self::$fixture_base_url ) !== 0 ) {
			return $response;
		}

		$parts = wp_parse_url( $url );
		$path  = $parts['path'] ?? '';
		$query = [];

		if ( isset( $parts['query'] ) ) {
			parse_str( $parts['query'], $query );
		}

		if ( $this->str_ends_with( $path, '/stellarwp/v4/products' ) ) {
			return $this->serve_licensing_fixture( $query );
		}

		if ( $this->str_ends_with( $path, '/stellarwp/v4/licenses/validate' ) ) {
			return $this->serve_validation_fixture( $parsed_args );
		}

		if ( $this->str_ends_with( $path, '/stellarwp/v4/catalog' ) ) {
			return $this->serve_catalog_fixture( $query );
		}

		return $response;
	}

	/**
	 * @param array $query Query parameters.
	 *
	 * @return array
	 */
	private function serve_licensing_fixture( array $query ): array {
		$key = strtolower( $query['key'] ?? '' );

		if ( $key === '' ) {
			return $this->fixture_json_response( [ 'error' => 'Missing key' ], 400 );
		}

		$file = codecept_data_dir( 'licensing/' . $key . '.json' );

		if ( ! file_exists( $file ) ) {
			return $this->fixture_json_response( [ 'error' => 'Key not recognized' ], 404 );
		}

		return $this->fixture_json_response_from_file( $file );
	}

	/**
	 * @param array $parsed_args Request arguments.
	 *
	 * @return array
	 */
	private function serve_validation_fixture( array $parsed_args ): array {
		$body = json_decode( $parsed_args['body'] ?? '{}', true );

		if ( ! is_array( $body ) ) {
			return $this->fixture_json_response( [ 'error' => 'Invalid body' ], 400 );
		}

		$key          = strtolower( $body['key'] ?? '' );
		$product_slug = $body['product_slug'] ?? '';
		$domain       = $body['domain'] ?? '';

		$file = codecept_data_dir( 'licensing/' . $key . '.json' );

		if ( ! file_exists( $file ) ) {
			return $this->fixture_json_response( [ 'error' => 'Key not recognized' ], 404 );
		}

		$data = json_decode( file_get_contents( $file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		if ( ! is_array( $data ) || ! isset( $data['products'] ) ) {
			return $this->fixture_json_response( [ 'error' => 'Fixture malformed' ], 500 );
		}

		$entry = null;
		foreach ( $data['products'] as $product ) {
			if ( ( $product['product_slug'] ?? '' ) === $product_slug ) {
				$entry = $product;
				break;
			}
		}

		if ( $entry === null ) {
			return $this->fixture_json_response(
				[
					'status' => 'not_found',
				],
				200
			);
		}

		$validation = [
			'status'       => $entry['validation_status'] ?? 'not_activated',
			'license'      => [
				'key'    => strtoupper( $key ),
				'status' => 'active',
			],
			'subscription' => [
				'product_slug'    => $entry['product_slug'],
				'tier'            => $entry['tier'] ?? '',
				'site_limit'      => $entry['activations']['site_limit'] ?? 0,
				'expiration_date' => $entry['expires'] ?? '',
				'status'          => $entry['status'] ?? '',
			],
		];

		if ( ! empty( $entry['installed_here'] ) ) {
			$validation['activation'] = [
				'domain'       => $domain,
				'activated_at' => gmdate( 'Y-m-d H:i:s' ),
			];
		}

		return $this->fixture_json_response( $validation );
	}

	/**
	 * @param array $query Query parameters.
	 *
	 * @return array
	 */
	private function serve_catalog_fixture( array $query ): array {
		$key = strtolower( $query['key'] ?? '' );

		$file = null;
		if ( $key !== '' ) {
			$candidate = codecept_data_dir( 'catalog/' . $key . '.json' );
			if ( file_exists( $candidate ) ) {
				$file = $candidate;
			}
		}

		if ( $file === null ) {
			$file = codecept_data_dir( 'catalog/default.json' );
		}

		if ( ! file_exists( $file ) ) {
			return $this->fixture_json_response( [ 'error' => 'No catalog fixture found' ], 500 );
		}

		return $this->fixture_json_response_from_file( $file );
	}

	/**
	 * @param string $file Absolute path to JSON file.
	 *
	 * @return array
	 */
	private function fixture_json_response_from_file( string $file ): array {
		$body = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		return [
			'headers'  => [],
			'body'     => $body,
			'response' => [
				'code'    => 200,
				'message' => 'OK',
			],
			'cookies'  => [],
			'filename' => null,
		];
	}

	/**
	 * @param mixed $data        Data to JSON-encode.
	 * @param int   $status_code HTTP status code.
	 *
	 * @return array
	 */
	private function fixture_json_response( $data, int $status_code = 200 ): array {
		return [
			'headers'  => [],
			'body'     => wp_json_encode( $data ),
			'response' => [
				'code'    => $status_code,
				'message' => $status_code === 200 ? 'OK' : 'Error',
			],
			'cookies'  => [],
			'filename' => null,
		];
	}

	/**
	 * PHP 7.4 compatible str_ends_with.
	 *
	 * @param string $haystack
	 * @param string $needle
	 *
	 * @return bool
	 */
	private function str_ends_with( string $haystack, string $needle ): bool {
		return substr( $haystack, -strlen( $needle ) ) === $needle;
	}
}
