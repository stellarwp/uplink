<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Features\Update;

use StellarWP\Uplink\Catalog\Catalog_Collection;
use StellarWP\Uplink\Catalog\Catalog_Repository;
use StellarWP\Uplink\Catalog\Contracts\Catalog_Client;
use StellarWP\Uplink\Features\Feature_Collection;
use StellarWP\Uplink\Features\Feature_Repository;
use StellarWP\Uplink\Features\Types\Plugin;
use StellarWP\Uplink\Features\Update\Resolve_Update_Data;
use StellarWP\Uplink\Features\Update\Update_Repository;
use StellarWP\Uplink\Tests\UplinkTestCase;
use WP_Error;

final class Update_RepositoryTest extends UplinkTestCase {

	/**
	 * The update repository under test.
	 *
	 * @var Update_Repository
	 */
	private Update_Repository $repository;

	/**
	 * Sets up the update repository and clears the transient before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->repository = $this->make_update_repository(
			$this->build_feature_collection(),
			$this->build_catalog_collection()
		);

		delete_transient( Update_Repository::TRANSIENT_KEY );
	}

	/**
	 * Clears the transient after each test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		delete_transient( Update_Repository::TRANSIENT_KEY );

		parent::tearDown();
	}

	/**
	 * Tests get returns feature data keyed by feature slug
	 * with WordPress-compatible fields.
	 *
	 * @return void
	 */
	public function test_it_returns_feature_data(): void {
		$result = $this->repository->get( 'test-key', 'example.com' );

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
					'is_available' => false,
					'authors'      => [ 'StellarWP' ],
				]
			)
		);

		$catalog = Catalog_Collection::from_array(
			[
				[
					'product_slug' => 'kadence',
					'tiers'        => [],
					'features'     => [
						[
							'feature_slug'      => 'available-feature',
							'type'              => 'plugin',
							'minimum_tier'      => 'kadence-basic',
							'is_dot_org'        => false,
							'download_url'      => 'https://example.com/available.zip',
							'version'           => '1.0.0',
							'name'              => 'Available Feature',
							'description'       => 'An available feature.',
							'category'          => '',
							'authors'           => [ 'StellarWP' ],
							'documentation_url' => '',
						],
						[
							'feature_slug'      => 'unavailable-feature',
							'type'              => 'plugin',
							'minimum_tier'      => 'kadence-pro',
							'is_dot_org'        => false,
							'download_url'      => 'https://example.com/unavailable.zip',
							'version'           => '1.0.0',
							'name'              => 'Unavailable Feature',
							'description'       => 'An unavailable feature.',
							'category'          => '',
							'authors'           => [ 'StellarWP' ],
							'documentation_url' => '',
						],
					],
				],
			]
		);

		$repository = $this->make_update_repository( $collection, $catalog );
		$result     = $repository->get( 'test-key', 'example.com' );

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
					'is_available' => true,
					'authors'      => [ 'StellarWP' ],
				]
			)
		);

		$collection->add(
			new Plugin(
				[
					'slug'         => 'dot-org-feature',
					'group'        => 'kadence',
					'tier'         => 'kadence-basic',
					'name'         => 'Dot Org Feature',
					'description'  => 'A feature on WordPress.org.',
					'plugin_file'  => 'dot-org-feature/dot-org-feature.php',
					'is_available' => true,
					'authors'      => [ 'StellarWP' ],
				]
			)
		);

		$catalog = Catalog_Collection::from_array(
			[
				[
					'product_slug' => 'kadence',
					'tiers'        => [],
					'features'     => [
						[
							'feature_slug'      => 'custom-feature',
							'type'              => 'plugin',
							'minimum_tier'      => 'kadence-basic',
							'is_dot_org'        => false,
							'download_url'      => 'https://example.com/custom.zip',
							'version'           => '1.0.0',
							'name'              => 'Custom Feature',
							'description'       => 'A custom feature.',
							'category'          => '',
							'authors'           => [ 'StellarWP' ],
							'documentation_url' => '',
						],
						[
							'feature_slug'      => 'dot-org-feature',
							'type'              => 'plugin',
							'minimum_tier'      => 'kadence-basic',
							'is_dot_org'        => true,
							'name'              => 'Dot Org Feature',
							'description'       => 'A feature on WordPress.org.',
							'category'          => '',
							'authors'           => [ 'StellarWP' ],
							'documentation_url' => '',
						],
					],
				],
			]
		);

		$repository = $this->make_update_repository( $collection, $catalog );
		$result     = $repository->get( 'test-key', 'example.com' );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'custom-feature', $result );
		$this->assertArrayNotHasKey( 'dot-org-feature', $result );
	}

	/**
	 * Tests that the fetched data is stored in a WordPress transient.
	 *
	 * @return void
	 */
	public function test_it_caches_in_transient(): void {
		$this->repository->get( 'test-key', 'example.com' );

		$cached = get_transient( Update_Repository::TRANSIENT_KEY );

		$this->assertIsArray( $cached );
		$this->assertArrayHasKey( 'kad-blocks-pro', $cached );
	}

	/**
	 * Tests get returns the cached transient on subsequent calls.
	 *
	 * @return void
	 */
	public function test_it_returns_cached_result(): void {
		$cached = [ 'my-plugin' => [ 'new_version' => '2.0.0' ] ];
		set_transient( Update_Repository::TRANSIENT_KEY, $cached, HOUR_IN_SECONDS );

		$result = $this->repository->get( 'test-key', 'example.com' );

		$this->assertSame( $cached, $result );
	}

	/**
	 * Tests refresh clears the cache and returns fresh data from the Feature_Repository.
	 *
	 * @return void
	 */
	public function test_refresh_clears_cache(): void {
		$cached = [ 'my-plugin' => [ 'new_version' => '2.0.0' ] ];
		set_transient( Update_Repository::TRANSIENT_KEY, $cached, HOUR_IN_SECONDS );

		$result = $this->repository->refresh( 'test-key', 'example.com' );

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

		$repository = $this->make_update_repository( $error, $this->build_catalog_collection() );
		$result     = $repository->get( 'test-key', 'example.com' );

		$this->assertInstanceOf( WP_Error::class, $result );

		$cached = get_transient( Update_Repository::TRANSIENT_KEY );

		$this->assertInstanceOf( WP_Error::class, $cached );
	}

	/**
	 * Tests that a cached WP_Error is returned without re-fetching.
	 *
	 * @return void
	 */
	public function test_it_returns_cached_wp_error(): void {
		$error = new WP_Error( 'test_error', 'Cached error' );
		set_transient( Update_Repository::TRANSIENT_KEY, $error );

		$result = $this->repository->get( 'test-key', 'example.com' );

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
					'is_available'      => true,
					'documentation_url' => 'https://www.kadencewp.com/help-center/',
					'authors'           => [ 'KadenceWP' ],
				]
			)
		);

		return $collection;
	}

	/**
	 * Builds a Catalog_Collection with matching catalog data for the default feature collection.
	 *
	 * @return Catalog_Collection
	 */
	private function build_catalog_collection(): Catalog_Collection {
		return Catalog_Collection::from_array(
			[
				[
					'product_slug' => 'kadence',
					'tiers'        => [],
					'features'     => [
						[
							'feature_slug'      => 'kad-blocks-pro',
							'type'              => 'plugin',
							'minimum_tier'      => 'kadence-basic',
							'plugin_file'       => 'kadence-blocks-pro/kadence-blocks-pro.php',
							'is_dot_org'        => false,
							'download_url'      => 'https://licensing.stellarwp.com/api/plugins/kad-blocks-pro',
							'version'           => '2.5.0',
							'name'              => 'Blocks Pro',
							'description'       => 'Premium Gutenberg blocks for advanced page building.',
							'category'          => 'blocks',
							'authors'           => [ 'KadenceWP' ],
							'documentation_url' => 'https://www.kadencewp.com/help-center/',
						],
					],
				],
			]
		);
	}

	/**
	 * Creates an Update_Repository with a mocked Feature_Repository and a
	 * real Catalog_Repository backed by a mocked Catalog_Client.
	 *
	 * Catalog_Repository is final so it cannot be mocked directly.
	 *
	 * @param Feature_Collection|WP_Error $feature_result The result to return from Feature_Repository::get().
	 * @param Catalog_Collection|WP_Error $catalog_result The result to return from Catalog_Repository::get().
	 *
	 * @return Update_Repository
	 */
	private function make_update_repository( $feature_result, $catalog_result ): Update_Repository {
		$feature_repository = $this->makeEmpty(
			Feature_Repository::class,
			[
				'get' => $feature_result,
			]
		);

		$client = $this->makeEmpty(
			Catalog_Client::class,
			[
				'get_catalog' => $catalog_result,
			]
		);

		delete_transient( Catalog_Repository::TRANSIENT_KEY );

		$catalog_repository = new Catalog_Repository( $client );
		$resolver           = new Resolve_Update_Data( $feature_repository, $catalog_repository );

		return new Update_Repository( $resolver );
	}
}
