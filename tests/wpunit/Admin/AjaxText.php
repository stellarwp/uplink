<?php

namespace wpunit\Admin;

use StellarWP\Uplink\Admin\Ajax;
use StellarWP\Uplink\Admin\License_Field;
use StellarWP\Uplink\Container;
use StellarWP\Uplink\Register;
use StellarWP\Uplink\Resources\Collection;
use StellarWP\Uplink\Tests\UplinkTestCase;
use StellarWP\Uplink\Uplink;

class AjaxText extends UplinkTestCase {

	public function _setUp() {
		parent::_setUp();

		$container = Container::init();
		$container->make( Collection::class );

		Register::plugin(
			'sample',
			'Lib Sample',
			'sample/index.php',
			Uplink::class,
			'1.0.10',
			Uplink::class
		);
	}

	public function test_validate_license() {
		$_POST            = [];
		$handler          = new Ajax();
		$invalid_response = [
			'status'  => 0,
			'message' => __( 'Invalid request: nonce field is expired. Please try again.', '%stellar-uplink-domain%' )
		];

		$this->assertSame(
			json_encode( $invalid_response ),
			$handler->validate_license(),
			'Should return invalid request message if nonce or key is missing is empty'
		);
		$_POST['_wpnonce'] = wp_create_nonce( License_Field::get_group_name() );
		$_POST['key']	   = 'sample';
		$_POST['plugin']   = 'sample/index.php';
		codecept_debug($handler->validate_license());
		$this->assertSame( json_encode( [
			'status' => 0
		] ), $handler->validate_license() );
	}

}
