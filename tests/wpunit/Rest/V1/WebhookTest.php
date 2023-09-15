<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Rest\V1;

use StellarWP\Uplink\Tests\RestTestCase;
use WP_REST_Request;

final class WebhookTest extends RestTestCase {

	public function test_token_storage_requires_authorization(): void {
		$request = new WP_REST_Request( 'POST', '/uplink/v1/webhooks/receive-token' );
		$request->set_param( 'token', 'testing 123' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 401, $response->get_status() );
	}

	public function test_it_stores_token_with_correct_nonce(): void {
		// TODO once token storing has been sorted out.
	}

}
