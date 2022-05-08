<?php

namespace StellarWP\Uplink\Tests;

use StellarWP\Uplink\Uplink;

class UplinkTestCase extends \Codeception\TestCase\WPTestCase {
	public function setUp() {
		// before
		parent::setUp();

		Uplink::init();
	}
}
