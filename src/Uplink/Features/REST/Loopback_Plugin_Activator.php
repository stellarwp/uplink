<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\REST;

use WP_Error;
use WP_Http_Cookie;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Sandboxed plugin activation via loopback HTTP request.
 *
 * POSTs to a private REST endpoint on the same site so that if the target
 * plugin fatals (exit/die/OOM), only the loopback request dies — the parent
 * process can inspect the HTTP response safely.
 *
 * This is an internal mechanism used by Zip_Strategy, not a public API.
 *
 * @since 3.0.0
 */
class Loopback_Plugin_Activator {

	/**
	 * REST API namespace for the loopback activation endpoint.
	 *
	 * @since 3.0.0
	 */
	public const REST_NAMESPACE = 'stellarwp/uplink/v1';

	/**
	 * REST API route for the loopback activation endpoint.
	 *
	 * @since 3.0.0
	 */
	public const REST_ROUTE = '/activate-plugin';

	/**
	 * Register the REST API route for sandboxed plugin activation.
	 *
	 * Called on `rest_api_init`. The route is used internally by the loopback
	 * request — it is not a public API for external consumers.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public static function register_rest_route(): void {
		register_rest_route( self::REST_NAMESPACE, self::REST_ROUTE, [
			'methods'             => 'POST',
			'callback'            => [ self::class, 'handle_rest_activate' ],
			'permission_callback' => static function () {
				return current_user_can( 'activate_plugins' );
			},
			'args'                => [
				'plugin_file' => [
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				],
			],
		] );
	}

	/**
	 * REST endpoint: activate a plugin in a sandboxed loopback request.
	 *
	 * Registered at `stellarwp/uplink/v1/activate-plugin`.
	 * If the plugin fatals (exit/die/OOM), only this request dies — the
	 * parent request inspects the HTTP response and reports the error.
	 *
	 * @since 3.0.0
	 *
	 * @param WP_REST_Request $request The REST request.
	 *
	 * @return WP_REST_Response
	 */
	public static function handle_rest_activate( WP_REST_Request $request ): WP_REST_Response {
		$plugin_file = $request->get_param( 'plugin_file' );

		if ( ! function_exists( 'activate_plugin' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$result = activate_plugin( $plugin_file );

		if ( is_wp_error( $result ) ) {
			return new WP_REST_Response( [
				'success' => false,
				'data'    => [
					'code'    => $result->get_error_code(),
					'message' => $result->get_error_message(),
				],
			], 200 );
		}

		return new WP_REST_Response( [ 'success' => true ], 200 );
	}

	/**
	 * Attempt to activate a plugin via a loopback HTTP request to the REST API.
	 *
	 * POSTs to the stellarwp/uplink/v1/activate-plugin endpoint on the same
	 * site. If the plugin fatals (exit/die/OOM), only the loopback dies — the
	 * parent process gets an HTTP error response it can inspect safely.
	 *
	 * Authentication uses WordPress cookie auth: current $_COOKIE values are
	 * forwarded and a wp_rest nonce is sent via the X-WP-Nonce header.
	 *
	 * @since 3.0.0
	 *
	 * @param string $plugin_file Plugin file path relative to plugins directory.
	 *
	 * @return true|WP_Error|null True on success, WP_Error on definitive failure,
	 *                            null when loopback infrastructure is unavailable.
	 */
	public function activate( string $plugin_file ) {
		$url = $this->get_loopback_url();

		if ( $url === null ) {
			return null;
		}

		$response = $this->do_loopback_request( $url, [
			'timeout'   => 30,
			'sslverify' => false,
			'blocking'  => true,
			'headers'   => [
				'Content-Type' => 'application/json',
				'X-WP-Nonce'  => wp_create_nonce( 'wp_rest' ),
			],
			'body'      => wp_json_encode( [ 'plugin_file' => $plugin_file ] ),
			'cookies'   => $this->get_loopback_cookies(),
		] );

		// Infrastructure failure — trigger fallback.
		if ( is_wp_error( $response ) ) {
			return null;
		}

		$status_code = wp_remote_retrieve_response_code( $response );

		// 401/403 likely means BasicAuth, auth wall, or REST disabled — fall back.
		if ( $status_code === 401 || $status_code === 403 ) {
			return null;
		}

		$response_body = wp_remote_retrieve_body( $response );
		$decoded       = json_decode( $response_body, true );

		// Successful JSON response from our REST handler.
		if ( is_array( $decoded ) && isset( $decoded['success'] ) ) {
			if ( $decoded['success'] === true ) {
				return true;
			}

			// Plugin returned a WP_Error during activation.
			$code    = $decoded['data']['code'] ?? 'activation_failed';
			$message = $decoded['data']['message'] ?? 'Unknown activation error.';

			return new WP_Error(
				$code,
				sprintf( 'Activation failed for "%s": %s', $plugin_file, $message )
			);
		}

		// Non-JSON response with error status code — plugin fatal (die/exit/OOM).
		if ( $status_code >= 400 || $status_code === 0 ) {
			return new WP_Error(
				'activation_fatal',
				sprintf(
					'Fatal error during activation of "%s": the plugin caused a fatal error (HTTP %d).',
					$plugin_file,
					$status_code
				)
			);
		}

		// 200 but no valid JSON — could be a redirect page, maintenance mode, etc.
		// Treat as infrastructure failure and fall back.
		return null;
	}

	/**
	 * Get the loopback URL for the REST activation endpoint.
	 *
	 * Protected so tests can override to return null or a custom URL.
	 *
	 * @since 3.0.0
	 *
	 * @return string|null The REST API URL, or null if unavailable.
	 */
	protected function get_loopback_url(): ?string {
		return rest_url( self::REST_NAMESPACE . self::REST_ROUTE );
	}

	/**
	 * Perform the loopback HTTP POST request.
	 *
	 * Protected so tests can override to return canned responses.
	 *
	 * @since 3.0.0
	 *
	 * @param string               $url  The URL to POST to.
	 * @param array<string, mixed> $args Arguments for wp_remote_post().
	 *
	 * @return array|\WP_Error The response array or WP_Error on failure.
	 */
	protected function do_loopback_request( string $url, array $args ) {
		return wp_remote_post( $url, $args );
	}

	/**
	 * Build WP_Http_Cookie objects from the current $_COOKIE superglobal.
	 *
	 * Forwards the current user's session cookies to the loopback request
	 * so the AJAX handler authenticates as the same user. Required for
	 * nonce verification and capability checks.
	 *
	 * @since 3.0.0
	 *
	 * @return WP_Http_Cookie[]
	 */
	private function get_loopback_cookies(): array {
		$cookies = [];

		foreach ( $_COOKIE as $name => $value ) {
			$cookies[] = new WP_Http_Cookie( [
				'name'  => $name,
				'value' => $value,
			] );
		}

		return $cookies;
	}

}
