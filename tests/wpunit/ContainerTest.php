<?php declare( strict_types=1 );

namespace wpunit;

use StellarWP\ContainerContract\ContainerInterface;
use StellarWP\Uplink\Config;
use StellarWP\Uplink\Tests\UplinkTestCase;
use StellarWP\Uplink\Uplink;

class ContainerTest extends UplinkTestCase {

	/**
	 * Test that the container is correctly instantiated.
	 */
	public function test_it_should_instantiate(): void {
		$container = Config::get_container();

		$this->assertInstanceOf( ContainerInterface::class, $container );
	}

	public function test_it_gets_the_uplink_assets_uri(): void {
		$uri = $this->container->get( Uplink::UPLINK_ASSETS_URI );

		$this->assertSame( 'http://wordpress.test/wp-content/plugins/uplink/src/assets', $uri );

		$uri = Config::get_container()->get( Uplink::UPLINK_ASSETS_URI );

		$this->assertSame( 'http://wordpress.test/wp-content/plugins/uplink/src/assets', $uri );
	}
}
