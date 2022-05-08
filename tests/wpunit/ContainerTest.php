<?php

namespace StellarWP\Uplink\Tests;

use StellarWP\Uplink\Container;

class ContainerTest extends UplinkTestCase {
	/**
	 * Test that the container is correctly instantiated.
	 *
	 * @test
	 */
	public function it_should_instantiate() {
		$container = Container::init();

		$this->assertInstanceOf( Container::class, $container );
	}
}
