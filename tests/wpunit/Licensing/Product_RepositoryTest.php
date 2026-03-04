<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Licensing;

use StellarWP\Uplink\Licensing\Error_Code;
use StellarWP\Uplink\Licensing\Fixture_Client;
use StellarWP\Uplink\Licensing\Product_Collection;
use StellarWP\Uplink\Licensing\Product_Repository;
use StellarWP\Uplink\Licensing\Results\Product_Entry;
use StellarWP\Uplink\Tests\UplinkTestCase;
use WP_Error;

final class Product_RepositoryTest extends UplinkTestCase {

	private Product_Repository $repository;

	protected function setUp(): void {
		parent::setUp();

		$client           = new Fixture_Client( codecept_data_dir( 'licensing' ) );
		$this->repository = new Product_Repository( $client );

		delete_transient( Product_Repository::TRANSIENT_KEY );
	}

	protected function tearDown(): void {
		delete_transient( Product_Repository::TRANSIENT_KEY );

		parent::tearDown();
	}

	public function test_get_fetches_on_cache_miss(): void {
		$result = $this->repository->get( 'LWSW-UNIFIED-PRO-2026', 'example.com' );

		$this->assertInstanceOf( Product_Collection::class, $result );
		$this->assertCount( 4, $result );

		foreach ( $result as $entry ) {
			$this->assertInstanceOf( Product_Entry::class, $entry );
		}
	}

	public function test_get_returns_collection_keyed_by_slug(): void {
		$result = $this->repository->get( 'LWSW-UNIFIED-PRO-2026', 'example.com' );

		$this->assertInstanceOf( Product_Collection::class, $result );

		$kadence = $result->get( 'kadence' );

		$this->assertInstanceOf( Product_Entry::class, $kadence );
		$this->assertSame( 'kadence', $kadence->get_product_slug() );
		$this->assertSame( 'kadence-pro', $kadence->get_tier() );

		$this->assertNull( $result->get( 'nonexistent' ) );
	}

	public function test_get_returns_cached_data(): void {
		$this->repository->get( 'LWSW-UNIFIED-PRO-2026', 'example.com' );

		$cached = get_transient( Product_Repository::TRANSIENT_KEY );

		$this->assertInstanceOf( Product_Collection::class, $cached );
		$this->assertCount( 4, $cached );
	}

	public function test_get_serves_from_transient(): void {
		$collection = Product_Collection::from_array(
			[
				Product_Entry::from_array(
					[
						'product_slug' => 'cached-product',
						'tier'         => 'pro',
						'status'       => 'active',
						'expires'      => '2026-12-31 23:59:59',
					]
				),
			]
		);

		set_transient( Product_Repository::TRANSIENT_KEY, $collection );

		$result = $this->repository->get( 'LWSW-UNIFIED-PRO-2026', 'example.com' );

		$this->assertInstanceOf( Product_Collection::class, $result );
		$this->assertCount( 1, $result );
		$this->assertSame( 'cached-product', $result->get( 'cached-product' )->get_product_slug() );
	}

	public function test_get_caches_wp_error(): void {
		$result = $this->repository->get( 'NON-EXISTENT-KEY', 'example.com' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( Error_Code::INVALID_KEY, $result->get_error_code() );

		$cached = get_transient( Product_Repository::TRANSIENT_KEY );

		$this->assertInstanceOf( WP_Error::class, $cached );
	}

	public function test_get_returns_cached_wp_error(): void {
		$error = new WP_Error( Error_Code::INVALID_KEY, 'Cached error' );
		set_transient( Product_Repository::TRANSIENT_KEY, $error );

		$result = $this->repository->get( 'LWSW-UNIFIED-PRO-2026', 'example.com' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Cached error', $result->get_error_message() );
	}

	public function test_refresh_clears_and_refetches(): void {
		$stale = Product_Collection::from_array(
			[
				Product_Entry::from_array(
					[
						'product_slug' => 'stale-product',
						'tier'         => 'pro',
						'status'       => 'active',
						'expires'      => '2026-12-31 23:59:59',
					]
				),
			]
		);

		set_transient( Product_Repository::TRANSIENT_KEY, $stale );

		$result = $this->repository->refresh( 'LWSW-UNIFIED-PRO-2026', 'example.com' );

		$this->assertInstanceOf( Product_Collection::class, $result );
		$this->assertCount( 4, $result );

		$this->assertNotNull( $result->get( 'give' ) );
		$this->assertNull( $result->get( 'stale-product' ) );
	}
}
