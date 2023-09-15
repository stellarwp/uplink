<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests;

use WP_REST_Server;

class RestTestCase extends UplinkTestCase {

	/**
	 * @var \WP_REST_Server
	 */
	protected $server;

	protected function setUp() {
		parent::setUp();

		global $wp_rest_server;
		$this->server = $wp_rest_server = new WP_REST_Server;
		do_action( 'rest_api_init' );
	}

	protected function tearDown() {
		// @phpstan-ignore-next-line
		parent::tearDown();

		global $wp_rest_server;
		$wp_rest_server = null;
	}

}
