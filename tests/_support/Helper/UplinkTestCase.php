<?php

namespace StellarWP\Uplink\Tests;

use StellarWP\Uplink\Config;
use StellarWP\Uplink\Uplink;

class UplinkTestCase extends \Codeception\TestCase\WPTestCase {
	public function setUp() {
		// before
		parent::setUp();

		$container = new Container();
		Config::set_container( $container );
		Config::set_hook_prefix( 'test' );

		Uplink::init();
	}
}
