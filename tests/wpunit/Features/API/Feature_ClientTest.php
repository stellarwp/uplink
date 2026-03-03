<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Features\API;

use StellarWP\Uplink\Features\API\Feature_Client;
use StellarWP\Uplink\Features\API\Fixture;
use StellarWP\Uplink\Features\Feature_Collection;
use StellarWP\Uplink\Features\Types\Built_In;
use StellarWP\Uplink\Features\Types\Feature;
use StellarWP\Uplink\Features\Types\Zip;
use StellarWP\Uplink\Tests\UplinkTestCase;
use stdClass;

final class Feature_ClientTest extends UplinkTestCase {

	/**
	 * The API client instance under test.
	 *
	 * @var Feature_Client
	 */
	private Feature_Client $client;

	/**
	 * Sets up the API client and clears the transient before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->client = new Feature_Client();

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
	 * Tests get_features returns a Feature_Collection instance.
	 *
	 * @return void
	 */
	public function test_it_returns_a_collection(): void {
		$features = $this->client->get_features();

		$this->assertInstanceOf( Feature_Collection::class, $features );
	}

	/**
	 * Tests that the feature catalog is stored in a WordPress transient after fetching.
	 *
	 * @return void
	 */
	public function test_it_caches_in_transient(): void {
		$this->client->get_features();

		$cached = get_transient( 'stellarwp_uplink_feature_catalog' );

		$this->assertInstanceOf( Feature_Collection::class, $cached );
	}

	/**
	 * Tests refresh clears and re-fetches the transient cache.
	 *
	 * @return void
	 */
	public function test_refresh_clears_transient(): void {
		$this->client->get_features();

		$this->assertInstanceOf( Feature_Collection::class, get_transient( 'stellarwp_uplink_feature_catalog' ) );

		$this->client->refresh();

		/**
		 * After refresh, a new transient is set immediately from the re-fetch.
		 * Verify it's still a Feature_Collection (was re-fetched, not stale).
		 */
		$this->assertInstanceOf( Feature_Collection::class, get_transient( 'stellarwp_uplink_feature_catalog' ) );
	}

	/**
	 * Tests that the cached collection is returned instead of fetching from the API.
	 *
	 * @return void
	 */
	public function test_it_returns_cached_collection(): void {
		$collection = new Feature_Collection();
		$collection->add( $this->makeEmpty( Feature::class, [ 'get_slug' => 'cached-feature' ] ) );

		set_transient( 'stellarwp_uplink_feature_catalog', $collection );

		$result = $this->client->get_features();

		$this->assertCount( 1, $result );
		$this->assertSame( 'cached-feature', $result['cached-feature']->get_slug() );
	}

	/**
	 * Tests that the fixture catalog is used when the STELLARWP_UPLINK_FEATURES_USE_FIXTURE_CATALOG constant is defined.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function test_it_uses_fixture_catalog_when_enabled(): void {
		add_filter(
			'plugins_api',
			static function ( $result, $action ) {
				if ( $action === 'plugin_information' ) {
					$response          = new stdClass();
					$response->version = '1.0.0';

					return $response;
				}

				return $result;
			},
			10,
			2 
		);

		define( 'STELLARWP_UPLINK_FEATURES_USE_FIXTURE_DATA', true );

		add_filter(
			'stellarwp_uplink_features_fixture_data',
			function ( array $data ): array {
				$data = Fixture::create(
					[
						Fixture::entry(
							[
								'slug'        => 'give-recurring-donations',
								'group'       => 'give',
								'tier'        => 'starter',
								'name'        => 'Recurring Donations',
								'description' => 'Monthly and annual subscriptions',
							]
						),
						Fixture::entry(
							[
								'slug'        => 'give-fee-recovery',
								'group'       => 'give',
								'tier'        => 'pro',
								'name'        => 'Fee Recovery',
								'description' => 'Let donors cover processing fees',
							]
						),
					]
				);

				return $data->get();
			}
		);

		$this->client = new Feature_Client();

		$this->client->register_type( 'zip', Zip::class );
		$this->client->register_type( 'built_in', Built_In::class );

		$features = $this->client->get_features();

		$this->assertInstanceOf( Feature_Collection::class, $features );

		$this->assertCount( 2, $features );
		$this->assertSame( 'give-recurring-donations', $features->get( 'give-recurring-donations' )->get_slug() );
		$this->assertSame( 'give-fee-recovery', $features->get( 'give-fee-recovery' )->get_slug() );
	}
}
