<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Features\API;

use StellarWP\Uplink\Catalog\Catalog_Collection;
use StellarWP\Uplink\Catalog\Contracts\Catalog_Client;
use StellarWP\Uplink\Catalog\Results\Product_Catalog;
use StellarWP\Uplink\Config;
use StellarWP\Uplink\Features\API\Update_Client;
use StellarWP\Uplink\Tests\UplinkTestCase;
use WP_Error;

final class Update_ClientTest extends UplinkTestCase {

	/**
	 * The update client under test.
	 *
	 * @var Update_Client
	 */
	private Update_Client $client;

	/**
	 * Sets up the update client and clears the transient before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->bind_fixture_catalog_client();

		$this->client = new Update_Client();

		delete_transient( 'stellarwp_uplink_update_check' );
	}

	/**
	 * Clears the transient after each test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		delete_transient( 'stellarwp_uplink_update_check' );

		parent::tearDown();
	}

	/**
	 * Tests check_updates returns feature data keyed by feature slug
	 * with WordPress-compatible fields.
	 *
	 * @return void
	 */
	public function test_it_returns_feature_data(): void {
		$result = $this->client->check_updates( 'test-key', 'example.com', [ 'my-plugin' => '1.0.0' ] );

		$this->assertIsArray( $result );
		$this->assertNotEmpty( $result );

		// Verify the result is keyed by feature slug.
		$this->assertArrayHasKey( 'kad-blocks-pro', $result );

		// Verify each entry has WordPress-compatible fields.
		$entry = $result['kad-blocks-pro'];
		$this->assertArrayHasKey( 'name', $entry );
		$this->assertArrayHasKey( 'slug', $entry );
		$this->assertArrayHasKey( 'new_version', $entry );
		$this->assertArrayHasKey( 'package', $entry );
		$this->assertArrayHasKey( 'url', $entry );
		$this->assertArrayHasKey( 'author', $entry );
		$this->assertArrayHasKey( 'sections', $entry );

		$this->assertSame( 'Blocks Pro', $entry['name'] );
		$this->assertSame( 'kad-blocks-pro', $entry['slug'] );
		$this->assertSame( 'https://licensing.stellarwp.com/api/plugins/kad-blocks-pro', $entry['package'] );
	}

	/**
	 * Tests that the fetched data is stored in a WordPress transient.
	 *
	 * @return void
	 */
	public function test_it_caches_in_transient(): void {
		$this->client->check_updates( 'test-key', 'example.com', [ 'my-plugin' => '1.0.0' ] );

		$cached = get_transient( 'stellarwp_uplink_update_check' );

		$this->assertIsArray( $cached );
		$this->assertArrayHasKey( 'kad-blocks-pro', $cached );
	}

	/**
	 * Tests check_updates returns the cached transient on subsequent calls.
	 *
	 * @return void
	 */
	public function test_it_returns_cached_result(): void {
		$cached = [ 'my-plugin' => [ 'new_version' => '2.0.0' ] ];
		set_transient( 'stellarwp_uplink_update_check', $cached, HOUR_IN_SECONDS );

		$result = $this->client->check_updates( 'test-key', 'example.com', [ 'my-plugin' => '1.0.0' ] );

		$this->assertSame( $cached, $result );
	}

	/**
	 * Tests refresh clears the cache and returns fresh data from the Catalog_Client.
	 *
	 * @return void
	 */
	public function test_refresh_clears_cache(): void {
		$cached = [ 'my-plugin' => [ 'new_version' => '2.0.0' ] ];
		set_transient( 'stellarwp_uplink_update_check', $cached, HOUR_IN_SECONDS );

		$result = $this->client->refresh( 'test-key', 'example.com', [ 'my-plugin' => '1.0.0' ] );

		// After refresh, stale data is replaced with fresh fixture data.
		$this->assertIsArray( $result );
		$this->assertArrayNotHasKey( 'my-plugin', $result );
		$this->assertArrayHasKey( 'kad-blocks-pro', $result );
	}

	/**
	 * Tests that a WP_Error from the Catalog_Client is cached and returned.
	 *
	 * @return void
	 */
	public function test_it_caches_wp_error(): void {
		$error = new WP_Error( 'test_error', 'API unavailable.' );

		Config::get_container()->singleton(
			Catalog_Client::class,
			$this->make_client( $error )
		);

		$client = new Update_Client();
		$result = $client->check_updates( 'test-key', 'example.com', [ 'my-plugin' => '1.0.0' ] );

		$this->assertInstanceOf( WP_Error::class, $result );

		$cached = get_transient( 'stellarwp_uplink_update_check' );

		$this->assertInstanceOf( WP_Error::class, $cached );
	}

	/**
	 * Tests that a cached WP_Error is returned without re-fetching.
	 *
	 * @return void
	 */
	public function test_it_returns_cached_wp_error(): void {
		$error = new WP_Error( 'test_error', 'Cached error' );
		set_transient( 'stellarwp_uplink_update_check', $error );

		$result = $this->client->check_updates( 'test-key', 'example.com', [ 'my-plugin' => '1.0.0' ] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Cached error', $result->get_error_message() );
	}

	/**
	 * Binds a mock Catalog_Client backed by the fixture JSON into the container.
	 *
	 * @return void
	 */
	private function bind_fixture_catalog_client(): void {
		Config::get_container()->singleton(
			Catalog_Client::class,
			$this->make_client( $this->build_catalog_from_fixture() )
		);
	}

	/**
	 * Builds a Catalog_Collection from the fixture JSON file.
	 *
	 * @return Catalog_Collection
	 */
	private function build_catalog_from_fixture(): Catalog_Collection {
		$json = file_get_contents( codecept_data_dir( 'catalog.json' ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$data = json_decode( $json, true );

		$collection = new Catalog_Collection();

		foreach ( $data as $item ) {
			/** @var array<string, mixed> $item */
			$collection->add( Product_Catalog::from_array( $item ) );
		}

		return $collection;
	}

	/**
	 * Creates a mock Catalog_Client that returns the given result.
	 *
	 * @param Catalog_Collection|WP_Error $result The result to return from get_catalog().
	 *
	 * @return Catalog_Client
	 */
	private function make_client( $result ): Catalog_Client {
		return $this->makeEmpty(
			Catalog_Client::class,
			[
				'get_catalog' => $result,
			]
		);
	}
}
