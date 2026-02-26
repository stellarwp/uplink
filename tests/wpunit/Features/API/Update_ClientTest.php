<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Features\API;

use StellarWP\Uplink\Features\API\Update_Client;
use StellarWP\Uplink\Tests\UplinkTestCase;

final class Update_ClientTest extends UplinkTestCase {

	/**
	 * Clears the update transient before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		delete_transient( 'stellarwp_uplink_update_check' );
	}

	/**
	 * Tests the stub check_updates method returns an empty array.
	 *
	 * @return void
	 */
	public function test_it_returns_empty_array(): void {
		$client = new Update_Client();

		$result = $client->check_updates( 'test-key', 'example.com', [ 'my-plugin' => '1.0.0' ] );

		$this->assertSame( [], $result );
	}

	/**
	 * Tests check_updates returns the cached transient on subsequent calls.
	 *
	 * @return void
	 */
	public function test_it_returns_cached_result(): void {
		$cached = [ 'my-plugin' => [ 'new_version' => '2.0.0' ] ];
		set_transient( 'stellarwp_uplink_update_check', $cached, HOUR_IN_SECONDS );

		$client = new Update_Client();
		$result = $client->check_updates( 'test-key', 'example.com', [ 'my-plugin' => '1.0.0' ] );

		$this->assertSame( $cached, $result );
	}

	/**
	 * Tests refresh clears the cache and re-fetches.
	 *
	 * @return void
	 */
	public function test_refresh_clears_cache(): void {
		$cached = [ 'my-plugin' => [ 'new_version' => '2.0.0' ] ];
		set_transient( 'stellarwp_uplink_update_check', $cached, HOUR_IN_SECONDS );

		$client = new Update_Client();
		$result = $client->refresh( 'test-key', 'example.com', [ 'my-plugin' => '1.0.0' ] );

		// The stub request() returns [], so after refresh we get the fresh (empty) response.
		$this->assertSame( [], $result );
	}
}
