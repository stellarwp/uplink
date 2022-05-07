<?php

namespace StellarWP\Network\Tests;

use StellarWP\Network\Container;

class ContainerTest extends NetworkTestCase {
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
