<?php

namespace wpunit\Admin;

use StellarWP\Uplink\Admin\Ajax;
use StellarWP\Uplink\Tests\UplinkTestCase;

class AjaxText extends UplinkTestCase {

	public function test_it_should_invalid_request_message() {
		$_POST            = [
			'key' => 'sample',
		];
		$handler          = new Ajax();
		$invalid_response = [
			'status'  => 0,
			'message' => __( 'Invalid request: nonce field is expired. Please try again.', '%stellar-uplink-domain%' )
		];

		$this->assertSame( json_encode( $invalid_response ), $handler->validate_license() );
	}

}
