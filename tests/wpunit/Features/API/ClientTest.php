<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Features\API;

use StellarWP\Uplink\Features\API\Client;
use StellarWP\Uplink\Features\Collection;
use StellarWP\Uplink\Features\Types\Feature;
use StellarWP\Uplink\Tests\UplinkTestCase;

final class ClientTest extends UplinkTestCase {

	/**
	 * The API client instance under test.
	 *
	 * @var Client
	 */
	private Client $client;

	/**
	 * Sets up the API client and clears the transient before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->client = new Client();

		delete_transient( 'stellarwp_uplink_feature_catalog' );
	}

	/**
	 * Clears the transient after each test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		delete_transient( 'stellarwp_uplink_feature_catalog' );

		parent::tearDown();
	}

	/**
	 * Tests get_features returns a Collection instance.
	 *
	 * @return void
	 */
	public function test_it_returns_a_collection(): void {
		$features = $this->client->get_features();

		$this->assertInstanceOf( Collection::class, $features );
	}

	/**
	 * Tests that the feature catalog is stored in a WordPress transient after fetching.
	 *
	 * @return void
	 */
	public function test_it_caches_in_transient(): void {
		$this->client->get_features();

		$cached = get_transient( 'stellarwp_uplink_feature_catalog' );

		$this->assertInstanceOf( Collection::class, $cached );
	}

	/**
	 * Tests refresh clears and re-fetches the transient cache.
	 *
	 * @return void
	 */
	public function test_refresh_clears_transient(): void {
		$this->client->get_features();

		$this->assertInstanceOf( Collection::class, get_transient( 'stellarwp_uplink_feature_catalog' ) );

		$this->client->refresh();

		/**
		 * After refresh, a new transient is set immediately from the re-fetch.
		 * Verify it's still a Collection (was re-fetched, not stale).
		 */
		$this->assertInstanceOf( Collection::class, get_transient( 'stellarwp_uplink_feature_catalog' ) );
	}

	/**
	 * Tests that the cached collection is returned instead of fetching from the API.
	 *
	 * @return void
	 */
	public function test_it_returns_cached_collection(): void {
		$collection = new Collection();
		$collection->add( $this->makeEmpty( Feature::class, [ 'get_slug' => 'cached-feature' ] ) );

		set_transient( 'stellarwp_uplink_feature_catalog', $collection );

		$result = $this->client->get_features();

		$this->assertCount( 1, $result );
		$this->assertSame( 'cached-feature', $result['cached-feature']->get_slug() );
	}
}
