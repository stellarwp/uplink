<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Catalog;

use StellarWP\Uplink\Catalog\Catalog_Collection;
use StellarWP\Uplink\Catalog\Catalog_Repository;
use StellarWP\Uplink\Catalog\Error_Code;
use StellarWP\Uplink\Catalog\Fixture_Client;
use StellarWP\Uplink\Catalog\Results\Product_Catalog;
use StellarWP\Uplink\Tests\UplinkTestCase;
use WP_Error;

final class Catalog_RepositoryTest extends UplinkTestCase {

	private Catalog_Repository $repository;

	protected function setUp(): void {
		parent::setUp();

		$client           = new Fixture_Client( codecept_data_dir( 'catalog.json' ) );
		$this->repository = new Catalog_Repository( $client );

		delete_transient( Catalog_Repository::TRANSIENT_KEY );
	}

	protected function tearDown(): void {
		delete_transient( Catalog_Repository::TRANSIENT_KEY );

		parent::tearDown();
	}

	public function test_get_fetches_on_cache_miss(): void {
		$result = $this->repository->get();

		$this->assertInstanceOf( Catalog_Collection::class, $result );
		$this->assertCount( 4, $result );

		foreach ( $result as $catalog ) {
			$this->assertInstanceOf( Product_Catalog::class, $catalog );
		}
	}

	public function test_get_stores_in_transient(): void {
		$this->repository->get();

		$cached = get_transient( Catalog_Repository::TRANSIENT_KEY );

		$this->assertInstanceOf( Catalog_Collection::class, $cached );
		$this->assertCount( 4, $cached );
	}

	public function test_get_serves_from_transient(): void {
		$stale = new Catalog_Collection();
		$stale->add( Product_Catalog::from_array( [
			'product_slug' => 'cached-product',
			'tiers'        => [ [ 'slug' => 'basic', 'name' => 'Basic', 'rank' => 1, 'purchase_url' => '' ] ],
			'features'     => [],
		] ) );

		set_transient( Catalog_Repository::TRANSIENT_KEY, $stale );

		$result = $this->repository->get();

		$this->assertInstanceOf( Catalog_Collection::class, $result );
		$this->assertCount( 1, $result );
		$this->assertSame( 'cached-product', $result->get( 'cached-product' )->get_product_slug() );
	}

	public function test_get_caches_wp_error(): void {
		$client     = new Fixture_Client( '/tmp/does-not-exist-' . uniqid() . '.json' );
		$repository = new Catalog_Repository( $client );
		$result     = $repository->get();

		$this->assertInstanceOf( WP_Error::class, $result );

		$cached = get_transient( Catalog_Repository::TRANSIENT_KEY );

		$this->assertInstanceOf( WP_Error::class, $cached );
	}

	public function test_get_returns_cached_wp_error(): void {
		$error = new WP_Error( Error_Code::INVALID_RESPONSE, 'Cached error' );
		set_transient( Catalog_Repository::TRANSIENT_KEY, $error );

		$result = $this->repository->get();

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Cached error', $result->get_error_message() );
	}

	public function test_refresh_clears_and_refetches(): void {
		$stale = new Catalog_Collection();
		$stale->add( Product_Catalog::from_array( [
			'product_slug' => 'stale-product',
			'tiers'        => [],
			'features'     => [],
		] ) );

		set_transient( Catalog_Repository::TRANSIENT_KEY, $stale );

		$result = $this->repository->refresh();

		$this->assertInstanceOf( Catalog_Collection::class, $result );
		$this->assertCount( 4, $result );

		$this->assertNotNull( $result->get( 'kadence' ) );
		$this->assertNull( $result->get( 'stale-product' ) );
	}
}
