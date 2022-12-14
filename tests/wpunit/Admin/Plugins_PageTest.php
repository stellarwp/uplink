<?php

namespace wpunit\Admin;

use StellarWP\Uplink\Admin\Plugins_Page;
use StellarWP\Uplink\Container;
use StellarWP\Uplink\Register;
use StellarWP\Uplink\Resources\Collection;
use StellarWP\Uplink\Tests\UplinkTestCase;
use StellarWP\Uplink\Uplink;

class Plugins_PageTest extends UplinkTestCase {

	public function setUp() {
		parent::setUp();

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

	public function test_it_should_return_null_on_non_plugin_php_page() {
		$handler = new Plugins_Page();
		$this->assertNull( $handler->display_plugin_messages( 'admin.php' ) );
	}

	public function test_it_should_bail_if_there_is_no_plugin() {
		$handler = new Plugins_Page();
		$this->assertNull( $handler->display_plugin_messages( 'plugins.php' ) );
	}

	public function test_add_notice_to_plugin_notices_should_return_same() {
		$handler = new Plugins_Page();
		$this->assertSame( [], $handler->add_notice_to_plugin_notices( [] ) );
	}

	public function test_add_notice_to_plugin_notices_should_return_updated() {
		$handler = new Plugins_Page();
		$handler->plugin_notice = [ 'slug' => 'sample', 'message_row_html' => '<div></div>' ];

		$this->assertSame( [ 'sample' => $handler->plugin_notice ], $handler->add_notice_to_plugin_notices( [] ) );
	}

}
