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

	/**
	 * The repository under test.
	 *
	 * @var Product_Repository
	 */
	private Product_Repository $repository;

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$client           = new Fixture_Client( codecept_data_dir( 'licensing' ) );
		$this->repository = new Product_Repository( $client );

		delete_transient( Product_Repository::TRANSIENT_KEY );
	}

	/**
	 * @return void
	 */
	protected function tearDown(): void {
		delete_transient( Product_Repository::TRANSIENT_KEY );

		parent::tearDown();
	}

	// ------------------------------------------------------------------
	// get() — cache miss
	// ------------------------------------------------------------------

	/**
	 * Tests that get() fetches from the client on cache miss.
	 *
	 * @return void
	 */
	public function test_get_fetches_on_cache_miss(): void {
		$result = $this->repository->get( 'LW-UNIFIED-PRO-2025', 'example.com' );

		$this->assertInstanceOf( Product_Collection::class, $result );
		$this->assertCount( 4, $result );

		foreach ( $result as $entry ) {
			$this->assertInstanceOf( Product_Entry::class, $entry );
		}
	}

	/**
	 * Tests that the collection is keyed by product slug.
	 *
	 * @return void
	 */
	public function test_get_returns_collection_keyed_by_slug(): void {
		$result = $this->repository->get( 'LW-UNIFIED-PRO-2025', 'example.com' );

		$this->assertInstanceOf( Product_Collection::class, $result );

		$kadence = $result->get( 'kadence' );

		$this->assertInstanceOf( Product_Entry::class, $kadence );
		$this->assertSame( 'kadence', $kadence->get_product_slug() );
		$this->assertSame( 'pro', $kadence->get_tier() );

		$this->assertNull( $result->get( 'nonexistent' ) );
	}

	// ------------------------------------------------------------------
	// get() — cache hit
	// ------------------------------------------------------------------

	/**
	 * Tests that get() returns cached collection on subsequent calls.
	 *
	 * @return void
	 */
	public function test_get_returns_cached_data(): void {
		$this->repository->get( 'LW-UNIFIED-PRO-2025', 'example.com' );

		$cached = get_transient( Product_Repository::TRANSIENT_KEY );

		$this->assertInstanceOf( Product_Collection::class, $cached );
		$this->assertCount( 4, $cached );
	}

	/**
	 * Tests that get() returns the transient value without calling the client.
	 *
	 * @return void
	 */
	public function test_get_serves_from_transient(): void {
		// Pre-populate the transient with a known collection.
		$collection = Product_Collection::from_array( [ Product_Entry::from_array( [
			'product_slug' => 'cached-product',
			'tier'         => 'pro',
			'status'       => 'active',
			'expires'      => '2026-12-31 23:59:59',
		] ) ] );

		set_transient( Product_Repository::TRANSIENT_KEY, $collection );

		// get() should return the pre-populated value, not fetch from client.
		$result = $this->repository->get( 'LW-UNIFIED-PRO-2025', 'example.com' );

		$this->assertInstanceOf( Product_Collection::class, $result );
		$this->assertCount( 1, $result );
		$this->assertSame( 'cached-product', $result->get( 'cached-product' )->get_product_slug() );
	}

	// ------------------------------------------------------------------
	// get() — caches WP_Error
	// ------------------------------------------------------------------

	/**
	 * Tests that get() caches and returns WP_Error from the client.
	 *
	 * @return void
	 */
	public function test_get_caches_wp_error(): void {
		$result = $this->repository->get( 'NON-EXISTENT-KEY', 'example.com' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( Error_Code::INVALID_KEY, $result->get_error_code() );

		// Verify the error is cached in the transient.
		$cached = get_transient( Product_Repository::TRANSIENT_KEY );

		$this->assertInstanceOf( WP_Error::class, $cached );
	}

	/**
	 * Tests that get() returns cached WP_Error on subsequent calls.
	 *
	 * @return void
	 */
	public function test_get_returns_cached_wp_error(): void {
		$error = new WP_Error( Error_Code::INVALID_KEY, 'Cached error' );
		set_transient( Product_Repository::TRANSIENT_KEY, $error );

		$result = $this->repository->get( 'LW-UNIFIED-PRO-2025', 'example.com' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Cached error', $result->get_error_message() );
	}

	// ------------------------------------------------------------------
	// refresh()
	// ------------------------------------------------------------------

	/**
	 * Tests that refresh() clears the transient and re-fetches.
	 *
	 * @return void
	 */
	public function test_refresh_clears_and_refetches(): void {
		// Prime the cache with a stale collection.
		$stale = Product_Collection::from_array( [ Product_Entry::from_array( [
			'product_slug' => 'stale-product',
			'tier'         => 'pro',
			'status'       => 'active',
			'expires'      => '2026-12-31 23:59:59',
		] ) ] );

		set_transient( Product_Repository::TRANSIENT_KEY, $stale );

		$result = $this->repository->refresh( 'LW-UNIFIED-PRO-2025', 'example.com' );

		$this->assertInstanceOf( Product_Collection::class, $result );
		$this->assertCount( 4, $result );

		// Verify the stale value was replaced.
		$this->assertNotNull( $result->get( 'give' ) );
		$this->assertNull( $result->get( 'stale-product' ) );
	}
}
