<?php

namespace wpunit;

use StellarWP\ContainerContract\ContainerInterface;
use StellarWP\Uplink\Config;
use StellarWP\Uplink\Tests\UplinkTestCase;

class ContainerTest extends UplinkTestCase {
	/**
	 * Test that the container is correctly instantiated.
	 *
	 * @test
	 */
	public function it_should_instantiate() {
		$container = Config::get_container();

		$this->assertInstanceOf( ContainerInterface::class, $container );
	}
}
