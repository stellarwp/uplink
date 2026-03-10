<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Catalog;

use StellarWP\Uplink\Catalog\Catalog_Repository;
use StellarWP\Uplink\Catalog\Contracts\Catalog_Client;
use StellarWP\Uplink\Catalog\Http_Client;
use StellarWP\Uplink\Tests\UplinkTestCase;

final class ProviderTest extends UplinkTestCase {

	public function test_it_registers_catalog_client(): void {
		$this->assertInstanceOf(
			Http_Client::class,
			$this->container->get( Catalog_Client::class )
		);
	}

	public function test_it_registers_catalog_repository(): void {
		$this->assertInstanceOf(
			Catalog_Repository::class,
			$this->container->get( Catalog_Repository::class )
		);
	}

	public function test_client_is_singleton(): void {
		$first  = $this->container->get( Catalog_Client::class );
		$second = $this->container->get( Catalog_Client::class );

		$this->assertSame( $first, $second );
	}

	public function test_repository_is_singleton(): void {
		$first  = $this->container->get( Catalog_Repository::class );
		$second = $this->container->get( Catalog_Repository::class );

		$this->assertSame( $first, $second );
	}
}
