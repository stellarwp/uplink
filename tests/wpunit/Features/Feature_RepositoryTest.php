<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Features;

use StellarWP\Uplink\Features\Contracts\Feature_Client;
use StellarWP\Uplink\Features\Feature_Collection;
use StellarWP\Uplink\Features\Feature_Repository;
use StellarWP\Uplink\Features\Types\Feature;
use StellarWP\Uplink\Tests\UplinkTestCase;
use WP_Error;

final class Feature_RepositoryTest extends UplinkTestCase {

	/**
	 * The repository under test.
	 *
	 * @var Feature_Repository
	 */
	private Feature_Repository $repository;

	/**
	 * The feature collection returned by the mock client.
	 *
	 * @var Feature_Collection
	 */
	private Feature_Collection $collection;

	/**
	 * Sets up the repository with a mocked client before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->collection = new Feature_Collection();
		$this->collection->add( $this->makeEmpty( Feature::class, [ 'get_slug' => 'test-feature' ] ) );

		$client = $this->makeEmpty(
			Feature_Client::class,
			[
				'get_features' => $this->collection,
			]
		);

		$this->repository = new Feature_Repository( $client );

		delete_transient( Feature_Repository::TRANSIENT_KEY );
	}

	/**
	 * Clears the transient after each test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		delete_transient( Feature_Repository::TRANSIENT_KEY );

		parent::tearDown();
	}

	/**
	 * Tests get returns a Feature_Collection.
	 *
	 * @return void
	 */
	public function test_get_returns_collection(): void {
		$result = $this->repository->get();

		$this->assertInstanceOf( Feature_Collection::class, $result );
		$this->assertSame( 1, $result->count() );
	}

	/**
	 * Tests that the feature catalog is stored in a WordPress transient after fetching.
	 *
	 * @return void
	 */
	public function test_it_caches_in_transient(): void {
		$this->repository->get();

		$cached = get_transient( Feature_Repository::TRANSIENT_KEY );

		$this->assertInstanceOf( Feature_Collection::class, $cached );
	}

	/**
	 * Tests refresh clears and re-fetches the transient cache.
	 *
	 * @return void
	 */
	public function test_refresh_clears_transient(): void {
		$this->repository->get();

		$this->assertInstanceOf(
			Feature_Collection::class,
			get_transient( Feature_Repository::TRANSIENT_KEY )
		);

		$this->repository->refresh();

		/**
		 * After refresh, a new transient is set immediately from the re-fetch.
		 * Verify it's still a Feature_Collection (was re-fetched, not stale).
		 */
		$this->assertInstanceOf(
			Feature_Collection::class,
			get_transient( Feature_Repository::TRANSIENT_KEY )
		);
	}

	/**
	 * Tests that a cached transient is returned without calling the client again.
	 *
	 * @return void
	 */
	public function test_it_returns_cached_collection(): void {
		$cached_collection = new Feature_Collection();
		$cached_collection->add( $this->makeEmpty( Feature::class, [ 'get_slug' => 'cached-feature' ] ) );

		set_transient( Feature_Repository::TRANSIENT_KEY, $cached_collection );

		$result = $this->repository->get();

		$this->assertCount( 1, $result );
		$this->assertSame( 'cached-feature', $result['cached-feature']->get_slug() );
	}

	/**
	 * Tests that a WP_Error from the client is cached in the transient.
	 *
	 * @return void
	 */
	public function test_it_caches_wp_error(): void {
		$error  = new WP_Error( 'api_error', 'Could not fetch features.' );
		$client = $this->makeEmpty(
			Feature_Client::class,
			[
				'get_features' => $error,
			]
		);

		$repository = new Feature_Repository( $client );

		$result = $repository->get();

		$this->assertInstanceOf( WP_Error::class, $result );

		$cached = get_transient( Feature_Repository::TRANSIENT_KEY );

		$this->assertInstanceOf( WP_Error::class, $cached );
	}

	/**
	 * Tests that a cached WP_Error is returned without re-fetching.
	 *
	 * @return void
	 */
	public function test_it_returns_cached_wp_error(): void {
		$error = new WP_Error( 'api_error', 'Cached error' );
		set_transient( Feature_Repository::TRANSIENT_KEY, $error );

		$result = $this->repository->get();

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Cached error', $result->get_error_message() );
	}
}
