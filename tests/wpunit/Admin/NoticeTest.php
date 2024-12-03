<?php

namespace wpunit\Admin;

use StellarWP\Uplink\Admin\Notice;
use StellarWP\Uplink\Tests\UplinkTestCase;

class NoticeTest extends UplinkTestCase {

	public function test_is_should_add_notice() {
		$notice = new Notice();
		$notice->add_notice( Notice::EXPIRED_KEY, 'uplink' );

		$notices = get_option( Notice::STORE_KEY );
		$this->assertTrue( $notices[ Notice::EXPIRED_KEY ][ 'uplink' ] );
	}

	public function test_it_should_display_notice() {
		$notice = new Notice();
		$notice->add_notice( Notice::EXPIRED_KEY, 'uplink' );

		$this->expectOutputString( '<div class="notice notice-warning"><p>Your license is expired</p></div>' );

		$notice->setup_notices();
	}

}
