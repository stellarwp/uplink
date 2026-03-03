<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Features\API;

use StellarWP\Uplink\Features\API\Fixture_Client;
use StellarWP\Uplink\Features\Feature_Collection;
use StellarWP\Uplink\Features\Types\Built_In;
use StellarWP\Uplink\Features\Types\Zip;
use StellarWP\Uplink\Tests\UplinkTestCase;
use stdClass;

final class ClientTest extends UplinkTestCase {

	/**
	 * The client instance under test.
	 *
	 * @var Fixture_Client
	 */
	private Fixture_Client $client;

	/**
	 * Sets up the client before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

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

		$this->client = new Fixture_Client(
			codecept_data_dir( 'features.json' )
		);

		$this->client->register_type( 'zip', Zip::class );
		$this->client->register_type( 'built_in', Built_In::class );
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
	 * Tests that subsequent calls return the same in-memory cached instance.
	 *
	 * @return void
	 */
	public function test_it_returns_cached_collection(): void {
		$first  = $this->client->get_features();
		$second = $this->client->get_features();

		$this->assertSame( $first, $second );
	}

	/**
	 * Tests that the Fixture_Client hydrates features from the JSON fixture file.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function test_it_hydrates_from_json(): void {
		$features = $this->client->get_features();

		$this->assertInstanceOf( Feature_Collection::class, $features );
		$this->assertGreaterThan( 0, count( $features ) );

		// Verify Zip features are hydrated correctly.
		$zip_feature = $features->get( 'give-stripe-gateway' );
		$this->assertInstanceOf( Zip::class, $zip_feature );
		$this->assertSame( 'give-stripe-gateway', $zip_feature->get_slug() );
		$this->assertSame( 'Stripe Gateway', $zip_feature->get_name() );
		$this->assertSame( 'zip', $zip_feature->get_type() );

		// Verify Built_In features are hydrated correctly.
		$built_in_feature = $features->get( 'kadence-row-layout-block' );
		$this->assertInstanceOf( Built_In::class, $built_in_feature );
		$this->assertSame( 'kadence-row-layout-block', $built_in_feature->get_slug() );
		$this->assertSame( 'built_in', $built_in_feature->get_type() );
	}

	/**
	 * Tests that the Fixture_Client returns a WP_Error for a missing file.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function test_it_returns_error_for_missing_file(): void {
		$fixture_client = new Fixture_Client(
			'/nonexistent/path/features.json'
		);

		$fixture_client->register_type( 'zip', Zip::class );
		$fixture_client->register_type( 'built_in', Built_In::class );

		$result = $fixture_client->get_features();

		$this->assertInstanceOf( \WP_Error::class, $result );
	}
}
