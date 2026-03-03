<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Licensing;

use StellarWP\Uplink\Licensing\Contracts\Licensing_Client;
use StellarWP\Uplink\Licensing\Fixture_Client;
use StellarWP\Uplink\Licensing\Product_Repository;
use StellarWP\Uplink\Tests\UplinkTestCase;

final class ProviderTest extends UplinkTestCase {

	public function test_it_registers_licensing_client(): void {
		$this->assertInstanceOf(
			Fixture_Client::class,
			$this->container->get( Licensing_Client::class )
		);
	}

	public function test_it_registers_product_repository(): void {
		$this->assertInstanceOf(
			Product_Repository::class,
			$this->container->get( Product_Repository::class )
		);
	}

	public function test_client_is_singleton(): void {
		$first  = $this->container->get( Licensing_Client::class );
		$second = $this->container->get( Licensing_Client::class );

		$this->assertSame( $first, $second );
	}

	public function test_repository_is_singleton(): void {
		$first  = $this->container->get( Product_Repository::class );
		$second = $this->container->get( Product_Repository::class );

		$this->assertSame( $first, $second );
	}
}
