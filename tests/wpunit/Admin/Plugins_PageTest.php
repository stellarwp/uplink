<?php

namespace wpunit\Admin;

use StellarWP\Uplink\Admin\Plugins_Page;
use StellarWP\Uplink\Register;
use StellarWP\Uplink\Tests\UplinkTestCase;
use StellarWP\Uplink\Uplink;

class Plugins_PageTest extends UplinkTestCase {

	protected function setUp(): void {
		parent::setUp();

		Register::plugin(
			'sample',
			'Lib Sample',
			'sample/index.php',
			Uplink::class,
			'1.0.10',
			Uplink::class
		);
	}

	public function test_it_should_return_null_on_non_plugin_php_page() {
		$handler = new Plugins_Page();
		$this->assertNull( $handler->display_plugin_messages( 'admin.php' ) );
	}

	public function test_it_should_bail_if_there_is_no_plugin() {
		$handler = new Plugins_Page();
		$this->assertNull( $handler->display_plugin_messages( 'plugins.php' ) );
	}
}
