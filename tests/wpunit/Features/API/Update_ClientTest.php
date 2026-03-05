<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Features\API;

use StellarWP\Uplink\Features\API\Update_Client;
use StellarWP\Uplink\Features\Feature_Collection;
use StellarWP\Uplink\Features\Feature_Repository;
use StellarWP\Uplink\Features\Types\Plugin;
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

		$this->client = new Update_Client( $this->make_repository( $this->build_feature_collection() ) );

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
		$result = $this->client->check_updates( 'test-key', 'example.com' );

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
		$this->assertSame( '2.5.0', $entry['new_version'] );
		$this->assertSame( 'https://licensing.stellarwp.com/api/plugins/kad-blocks-pro', $entry['package'] );
	}

	/**
	 * Tests that only available features are included in the result.
	 *
	 * @return void
	 */
	public function test_it_only_includes_available_features(): void {
		$collection = new Feature_Collection();

		$collection->add(
			new Plugin(
				[
					'slug'         => 'available-feature',
					'group'        => 'kadence',
					'tier'         => 'kadence-basic',
					'name'         => 'Available Feature',
					'description'  => 'An available feature.',
					'plugin_file'  => 'available-feature/available-feature.php',
					'download_url' => 'https://example.com/available.zip',
					'new_version'  => '1.0.0',
					'is_available' => true,
					'authors'      => [ 'StellarWP' ],
				]
			)
		);

		$collection->add(
			new Plugin(
				[
					'slug'         => 'unavailable-feature',
					'group'        => 'kadence',
					'tier'         => 'kadence-pro',
					'name'         => 'Unavailable Feature',
					'description'  => 'An unavailable feature.',
					'plugin_file'  => 'unavailable-feature/unavailable-feature.php',
					'download_url' => 'https://example.com/unavailable.zip',
					'new_version'  => '1.0.0',
					'is_available' => false,
					'authors'      => [ 'StellarWP' ],
				]
			)
		);

		$client = new Update_Client( $this->make_repository( $collection ) );
		$result = $client->check_updates( 'test-key', 'example.com' );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'available-feature', $result );
		$this->assertArrayNotHasKey( 'unavailable-feature', $result );
	}

	/**
	 * Tests that dot-org features are excluded since WordPress.org serves their updates.
	 *
	 * @return void
	 */
	public function test_it_excludes_dot_org_features(): void {
		$collection = new Feature_Collection();

		$collection->add(
			new Plugin(
				[
					'slug'         => 'custom-feature',
					'group'        => 'kadence',
					'tier'         => 'kadence-basic',
					'name'         => 'Custom Feature',
					'description'  => 'A custom feature.',
					'plugin_file'  => 'custom-feature/custom-feature.php',
					'download_url' => 'https://example.com/custom.zip',
					'new_version'  => '1.0.0',
					'is_available' => true,
					'is_dot_org'   => false,
					'authors'      => [ 'StellarWP' ],
				]
			)
		);

		$collection->add(
			new Plugin(
				[
					'slug'         => 'dotorg-feature',
					'group'        => 'kadence',
					'tier'         => 'kadence-basic',
					'name'         => 'Dot Org Feature',
					'description'  => 'A feature on WordPress.org.',
					'plugin_file'  => 'dotorg-feature/dotorg-feature.php',
					'download_url' => '',
					'new_version'  => '2.0.0',
					'is_available' => true,
					'is_dot_org'   => true,
					'authors'      => [ 'StellarWP' ],
				]
			)
		);

		$client = new Update_Client( $this->make_repository( $collection ) );
		$result = $client->check_updates( 'test-key', 'example.com' );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'custom-feature', $result );
		$this->assertArrayNotHasKey( 'dotorg-feature', $result );
	}

	/**
	 * Tests that the fetched data is stored in a WordPress transient.
	 *
	 * @return void
	 */
	public function test_it_caches_in_transient(): void {
		$this->client->check_updates( 'test-key', 'example.com' );

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

		$result = $this->client->check_updates( 'test-key', 'example.com' );

		$this->assertSame( $cached, $result );
	}

	/**
	 * Tests refresh clears the cache and returns fresh data from the Feature_Repository.
	 *
	 * @return void
	 */
	public function test_refresh_clears_cache(): void {
		$cached = [ 'my-plugin' => [ 'new_version' => '2.0.0' ] ];
		set_transient( 'stellarwp_uplink_update_check', $cached, HOUR_IN_SECONDS );

		$result = $this->client->refresh( 'test-key', 'example.com' );

		// After refresh, stale data is replaced with fresh fixture data.
		$this->assertIsArray( $result );
		$this->assertArrayNotHasKey( 'my-plugin', $result );
		$this->assertArrayHasKey( 'kad-blocks-pro', $result );
	}

	/**
	 * Tests that a WP_Error from the Feature_Repository is cached and returned.
	 *
	 * @return void
	 */
	public function test_it_caches_wp_error(): void {
		$error = new WP_Error( 'test_error', 'API unavailable.' );

		$client = new Update_Client( $this->make_repository( $error ) );
		$result = $client->check_updates( 'test-key', 'example.com' );

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

		$result = $this->client->check_updates( 'test-key', 'example.com' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Cached error', $result->get_error_message() );
	}

	/**
	 * Builds a Feature_Collection with a Plugin feature matching the catalog fixture data.
	 *
	 * @return Feature_Collection
	 */
	private function build_feature_collection(): Feature_Collection {
		$collection = new Feature_Collection();

		$collection->add(
			new Plugin(
				[
					'slug'              => 'kad-blocks-pro',
					'group'             => 'kadence',
					'tier'              => 'kadence-basic',
					'name'              => 'Blocks Pro',
					'description'       => 'Premium Gutenberg blocks for advanced page building.',
					'plugin_file'       => 'kadence-blocks-pro/kadence-blocks-pro.php',
					'download_url'      => 'https://licensing.stellarwp.com/api/plugins/kad-blocks-pro',
					'new_version'       => '2.5.0',
					'is_available'      => true,
					'documentation_url' => 'https://www.kadencewp.com/help-center/',
					'authors'           => [ 'KadenceWP' ],
				]
			)
		);

		return $collection;
	}

	/**
	 * Creates a mock Feature_Repository that returns the given result.
	 *
	 * @param Feature_Collection|WP_Error $result The result to return from get().
	 *
	 * @return Feature_Repository
	 */
	private function make_repository( $result ): Feature_Repository {
		return $this->makeEmpty(
			Feature_Repository::class,
			[
				'get' => $result,
			]
		);
	}
}
