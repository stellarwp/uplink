<?php

namespace StellarWP\Network\Tests;

use Codeception\PHPUnit\TestCase;
use StellarWP\Network\Container;

class ContainerTest extends \Codeception\TestCase\WPTestCase {
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
