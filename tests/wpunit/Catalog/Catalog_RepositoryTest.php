<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Catalog;

use StellarWP\Uplink\Catalog\Catalog_Collection;
use StellarWP\Uplink\Catalog\Catalog_Repository;
use StellarWP\Uplink\Catalog\Clients\Catalog_Client;
use StellarWP\Uplink\Catalog\Clients\Fixture_Client;
use StellarWP\Uplink\Catalog\Results\Product_Catalog;
use StellarWP\Uplink\Catalog\Error_Code;
use StellarWP\Uplink\Tests\Traits\With_Uopz;
use StellarWP\Uplink\Tests\UplinkTestCase;
use WP_Error;

final class Catalog_RepositoryTest extends UplinkTestCase {

	use With_Uopz;

	private Catalog_Repository $repository;

	protected function setUp(): void {
		parent::setUp();

		$client           = new Fixture_Client( codecept_data_dir( 'catalog/default.json' ) );
		$this->repository = new Catalog_Repository( $client );

		delete_option( Catalog_Repository::CATALOG_STATE_OPTION_NAME );
	}

	protected function tearDown(): void {
		delete_option( Catalog_Repository::CATALOG_STATE_OPTION_NAME );

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

	public function test_get_stores_in_option(): void {
		$this->repository->get();

		$state = get_option( Catalog_Repository::CATALOG_STATE_OPTION_NAME );

		$this->assertIsArray( $state );
		$this->assertArrayHasKey( 'collection', $state );
		$this->assertIsArray( $state['collection'] );
		$this->assertCount( 4, $state['collection'] );
	}

	public function test_get_serves_from_option(): void {
		$stale = [
			'collection'      => [
				[
					'product_slug' => 'cached-product',
					'tiers'        => [
						[
							'slug'         => 'basic',
							'name'         => 'Basic',
							'rank'         => 1,
							'purchase_url' => '',
						],
					],
					'features'     => [],
				],
			],
			'last_success_at' => time(),
			'last_failure_at' => null,
			'last_error'      => null,
		];

		update_option( Catalog_Repository::CATALOG_STATE_OPTION_NAME, $stale );

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

		$state = get_option( Catalog_Repository::CATALOG_STATE_OPTION_NAME );

		$this->assertIsArray( $state );
		$this->assertNull( $state['collection'] );
		$this->assertInstanceOf( WP_Error::class, $state['last_error'] );
	}

	public function test_get_preserves_collection_on_error(): void {
		// Seed a valid collection first via a successful fetch.
		$this->repository->get();

		$state = get_option( Catalog_Repository::CATALOG_STATE_OPTION_NAME );
		$this->assertIsArray( $state['collection'] );
		$this->assertCount( 4, $state['collection'] );

		// Simulate an API failure using a mock client — refresh writes to the shared option.
		$error      = new WP_Error( 'catalog_error', 'API unavailable.' );
		$bad_client = $this->makeEmpty( Catalog_Client::class, [ 'get_catalog' => $error ] );
		( new Catalog_Repository( $bad_client ) )->refresh();

		// get() should still return the previously stored collection.
		$result = $this->repository->get();

		$this->assertInstanceOf( Catalog_Collection::class, $result );
		$this->assertCount( 4, $result );

		// The error is also persisted alongside the preserved collection.
		$this->assertInstanceOf( WP_Error::class, $this->repository->get_last_error() );
	}

	// -------------------------------------------------------------------------
	// get_cached
	// -------------------------------------------------------------------------

	public function test_get_cached_returns_null_when_no_data_stored(): void {
		$this->assertNull( $this->repository->get_cached() );
	}

	public function test_get_cached_returns_collection_after_successful_fetch(): void {
		$this->repository->get();

		$result = $this->repository->get_cached();

		$this->assertInstanceOf( Catalog_Collection::class, $result );
		$this->assertCount( 4, $result );
	}

	public function test_get_cached_returns_null_when_only_error_stored(): void {
		$this->repository->set_catalog( new WP_Error( Error_Code::INVALID_RESPONSE, 'API failure' ) );

		$this->assertNull( $this->repository->get_cached() );
	}

	public function test_get_cached_does_not_trigger_api_fetch(): void {
		$client = $this->makeEmpty(
			Catalog_Client::class,
			[
				'get_catalog' => function () {
					$this->fail( 'get_catalog() should not be called from get_cached().' );
				},
			]
		);

		$repository = new Catalog_Repository( $client );

		$this->assertNull( $repository->get_cached() );
	}

	// -------------------------------------------------------------------------
	// Error throttling
	// -------------------------------------------------------------------------

	public function test_set_catalog_collection_clears_last_failure_at(): void {
		$this->repository->set_catalog( new WP_Error( Error_Code::INVALID_RESPONSE, 'API failure' ) );

		$this->assertNotNull( $this->repository->get_last_failure_at() );

		$this->repository->refresh(); // bypasses throttle, triggers successful fetch via the fixture client

		$this->assertNull( $this->repository->get_last_failure_at() );
	}

	public function test_get_returns_cached_error_when_within_throttle_window(): void {
		// Write error state at a fixed time.
		$this->set_fn_return( 'time', 1000000 );
		$this->repository->set_catalog( new WP_Error( Error_Code::INVALID_RESPONSE, 'API failure' ) );

		// Advance to 30 s later — still within the 60 s TTL.
		$this->set_fn_return( 'time', 1000030 );

		$result = $this->repository->get();

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( Error_Code::INVALID_RESPONSE, $result->get_error_code() );
	}

	public function test_get_retries_api_after_throttle_window_expires(): void {
		// Write error state at a fixed time.
		$this->set_fn_return( 'time', 1000000 );
		$this->repository->set_catalog( new WP_Error( Error_Code::INVALID_RESPONSE, 'API failure' ) );

		// Advance past the 60 s TTL.
		$this->set_fn_return( 'time', 1000061 );

		$result = $this->repository->get();

		$this->assertInstanceOf( Catalog_Collection::class, $result );
		$this->assertCount( 4, $result );
	}

	public function test_successful_fetch_clears_error_state(): void {
		// Write error state at a fixed time.
		$this->set_fn_return( 'time', 1000000 );
		$this->repository->set_catalog( new WP_Error( Error_Code::INVALID_RESPONSE, 'API failure' ) );

		$this->assertNotNull( $this->repository->get_last_failure_at() );
		$this->assertNotNull( $this->repository->get_last_error() );

		// Advance past TTL so the request is not throttled and reaches the API.
		$this->set_fn_return( 'time', 1000061 );

		$this->repository->get();

		$this->assertNull( $this->repository->get_last_failure_at() );
		$this->assertNull( $this->repository->get_last_error() );
	}

	public function test_refresh_clears_and_refetches(): void {
		$stale = [
			'collection'      => [
				[
					'product_slug' => 'stale-product',
					'tiers'        => [],
					'features'     => [],
				],
			],
			'last_success_at' => time() - 100,
			'last_failure_at' => null,
			'last_error'      => null,
		];

		update_option( Catalog_Repository::CATALOG_STATE_OPTION_NAME, $stale );

		$result = $this->repository->refresh();

		$this->assertInstanceOf( Catalog_Collection::class, $result );
		$this->assertCount( 4, $result );

		$this->assertNotNull( $result->get( 'kadence' ) );
		$this->assertNull( $result->get( 'stale-product' ) );
	}
}
