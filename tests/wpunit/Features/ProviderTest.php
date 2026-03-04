<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Features;

use StellarWP\Uplink\Features\API\Client;
use StellarWP\Uplink\Features\Manager;
use StellarWP\Uplink\Features\Strategy\Resolver;
use StellarWP\Uplink\Tests\UplinkTestCase;

final class ProviderTest extends UplinkTestCase {

	/**
	 * Tests that the API Client is registered as a singleton in the container.
	 *
	 * @return void
	 */
	public function test_it_registers_client(): void {
		$this->assertInstanceOf( Client::class, $this->container->get( Client::class ) );
	}

	/**
	 * Tests that the Strategy Resolver is registered as a singleton in the container.
	 *
	 * @return void
	 */
	public function test_it_registers_resolver(): void {
		$this->assertInstanceOf( Resolver::class, $this->container->get( Resolver::class ) );
	}

	/**
	 * Tests that the Feature Manager is registered as a singleton in the container.
	 *
	 * @return void
	 */
	public function test_it_registers_manager(): void {
		$this->assertInstanceOf( Manager::class, $this->container->get( Manager::class ) );
	}
}
