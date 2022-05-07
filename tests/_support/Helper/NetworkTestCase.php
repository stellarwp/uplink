<?php

namespace StellarWP\Network\Tests;

use StellarWP\Network\Network;

class NetworkTestCase extends \Codeception\TestCase\WPTestCase {
	public function setUp() {
		// before
		parent::setUp();

		Network::init();
	}
}
