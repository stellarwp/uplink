<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Features\REST;

use StellarWP\Uplink\Features\REST\Loopback_Plugin_Activator;
use StellarWP\Uplink\Tests\UplinkTestCase;

/**
 * Tests for the Loopback_Plugin_Activator extracted from Zip_Strategy.
 *
 * Uses a Testable subclass that overrides get_loopback_url() and
 * do_loopback_request() so no real HTTP requests are made.
 *
 * @see Loopback_Plugin_Activator
 */
final class LoopbackPluginActivatorTest extends UplinkTestCase {

	/**
	 * activate() returns true when the REST endpoint returns success JSON.
	 */
	public function test_activate_returns_true_on_success(): void {
		$activator = new Testable_Loopback_Plugin_Activator();
		$activator->set_loopback_response( [
			'response' => [ 'code' => 200 ],
			'body'     => wp_json_encode( [ 'success' => true ] ),
		] );

		$result = $activator->activate( 'test-feature/test-feature.php' );

		$this->assertTrue( $result );
	}

	/**
	 * activate() returns WP_Error with the plugin's error code when the
	 * REST endpoint reports an activation error.
	 */
	public function test_activate_returns_wp_error_on_activation_error(): void {
		$activator = new Testable_Loopback_Plugin_Activator();
		$activator->set_loopback_response( [
			'response' => [ 'code' => 200 ],
			'body'     => wp_json_encode( [
				'success' => false,
				'data'    => [
					'code'    => 'plugin_invalid',
					'message' => 'Plugin file does not exist.',
				],
			] ),
		] );

		$result = $activator->activate( 'test-feature/test-feature.php' );

		$this->assertWPError( $result );
		$this->assertSame( 'plugin_invalid', $result->get_error_code() );
	}

	/**
	 * activate() returns activation_fatal WP_Error on HTTP 500 (plugin fatal).
	 */
	public function test_activate_returns_fatal_error_on_http_500(): void {
		$activator = new Testable_Loopback_Plugin_Activator();
		$activator->set_loopback_response( [
			'response' => [ 'code' => 500 ],
			'body'     => '<html><body>Internal Server Error</body></html>',
		] );

		$result = $activator->activate( 'test-feature/test-feature.php' );

		$this->assertWPError( $result );
		$this->assertSame( 'activation_fatal', $result->get_error_code() );
		$this->assertStringContainsString( 'HTTP 500', $result->get_error_message() );
	}

	/**
	 * activate() returns null (infrastructure failure) when the HTTP request
	 * itself fails (e.g. connection refused).
	 */
	public function test_activate_returns_null_on_connection_failure(): void {
		$activator = new Testable_Loopback_Plugin_Activator();
		$activator->set_loopback_response( new \WP_Error( 'http_request_failed', 'Connection refused' ) );

		$result = $activator->activate( 'test-feature/test-feature.php' );

		$this->assertNull( $result );
	}

	/**
	 * activate() returns null when the REST endpoint returns 401 (auth wall).
	 */
	public function test_activate_returns_null_on_401(): void {
		$activator = new Testable_Loopback_Plugin_Activator();
		$activator->set_loopback_response( [
			'response' => [ 'code' => 401 ],
			'body'     => 'Unauthorized',
		] );

		$result = $activator->activate( 'test-feature/test-feature.php' );

		$this->assertNull( $result );
	}

	/**
	 * activate() returns null when the REST endpoint returns 403 (forbidden).
	 */
	public function test_activate_returns_null_on_403(): void {
		$activator = new Testable_Loopback_Plugin_Activator();
		$activator->set_loopback_response( [
			'response' => [ 'code' => 403 ],
			'body'     => 'Forbidden',
		] );

		$result = $activator->activate( 'test-feature/test-feature.php' );

		$this->assertNull( $result );
	}

	/**
	 * activate() returns null when the loopback URL is unavailable.
	 */
	public function test_activate_returns_null_when_url_unavailable(): void {
		$activator = new Testable_Loopback_Plugin_Activator();
		$activator->set_loopback_url( null );

		$result = $activator->activate( 'test-feature/test-feature.php' );

		$this->assertNull( $result );
	}

	/**
	 * activate() returns null when the response is HTTP 200 but non-JSON
	 * (e.g. maintenance mode page, redirect page).
	 */
	public function test_activate_returns_null_on_200_non_json(): void {
		$activator = new Testable_Loopback_Plugin_Activator();
		$activator->set_loopback_response( [
			'response' => [ 'code' => 200 ],
			'body'     => '<html><body>Maintenance mode</body></html>',
		] );

		$result = $activator->activate( 'test-feature/test-feature.php' );

		$this->assertNull( $result );
	}

}

/**
 * Testable subclass that overrides HTTP methods for unit testing.
 *
 * Allows tests to inject canned loopback responses without making real
 * HTTP requests.
 */
class Testable_Loopback_Plugin_Activator extends Loopback_Plugin_Activator {

	/**
	 * @var array|\WP_Error|null Canned response from do_loopback_request().
	 */
	private $loopback_response;

	/**
	 * @var string|null|false Overridden loopback URL. False means "not overridden".
	 */
	private $loopback_url = false;

	/**
	 * Set the canned response for do_loopback_request().
	 *
	 * @param array|\WP_Error $response The canned response.
	 */
	public function set_loopback_response( $response ): void {
		$this->loopback_response = $response;
	}

	/**
	 * Override the loopback URL. Pass null to simulate unavailable loopback.
	 *
	 * @param string|null $url The URL or null.
	 */
	public function set_loopback_url( ?string $url ): void {
		$this->loopback_url = $url;
	}

	/** @inheritDoc */
	protected function get_loopback_url(): ?string {
		if ( $this->loopback_url !== false ) {
			return $this->loopback_url;
		}

		return parent::get_loopback_url();
	}

	/** @inheritDoc */
	protected function do_loopback_request( string $url, array $args ) {
		if ( $this->loopback_response !== null ) {
			return $this->loopback_response;
		}

		return parent::do_loopback_request( $url, $args );
	}
}
