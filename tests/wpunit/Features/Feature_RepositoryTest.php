<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Features;

use ReflectionMethod;
use StellarWP\Uplink\Catalog\Catalog_Repository;
use StellarWP\Uplink\Catalog\Fixture_Client as Catalog_Fixture;
use StellarWP\Uplink\Catalog\Results\Catalog_Feature;
use StellarWP\Uplink\Catalog\Results\Catalog_Tier;
use StellarWP\Uplink\Catalog\Results\Product_Catalog;
use StellarWP\Uplink\Catalog\Results\Tier_Collection;
use StellarWP\Uplink\Features\Error_Code;
use StellarWP\Uplink\Features\Feature_Collection;
use StellarWP\Uplink\Features\Feature_Repository;
use StellarWP\Uplink\Features\Types\Flag;
use StellarWP\Uplink\Features\Types\Zip;
use StellarWP\Uplink\Licensing\Contracts\Licensing_Client;
use StellarWP\Uplink\Licensing\Fixture_Client as Licensing_Fixture;
use StellarWP\Uplink\Licensing\Product_Repository;
use StellarWP\Uplink\Tests\UplinkTestCase;
use WP_Error;

final class Feature_RepositoryTest extends UplinkTestCase {

	/**
	 * Clears all relevant transients before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		delete_transient( Feature_Repository::TRANSIENT_KEY );
		delete_transient( Catalog_Repository::TRANSIENT_KEY );
		delete_transient( Product_Repository::TRANSIENT_KEY );
	}

	/**
	 * Clears all relevant transients after each test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		delete_transient( Feature_Repository::TRANSIENT_KEY );
		delete_transient( Catalog_Repository::TRANSIENT_KEY );
		delete_transient( Product_Repository::TRANSIENT_KEY );

		parent::tearDown();
	}

	/**
	 * Creates a Feature_Repository backed by the real fixture files.
	 *
	 * @param string|null $licensing_override Optional. Pass a key for the Licensing_Fixture,
	 *                                        or null to use a mock that returns a WP_Error.
	 *
	 * @return Feature_Repository
	 */
	private function make_repository( ?string $licensing_override = null ): Feature_Repository {
		$catalog_client = new Catalog_Fixture( codecept_data_dir( 'catalog.json' ) );

		if ( $licensing_override === null ) {
			$licensing_client = $this->makeEmpty(
				Licensing_Client::class,
				[ 'get_products' => new WP_Error( 'license_error', 'Licensing failed.' ) ]
			);
		} else {
			$licensing_client = new Licensing_Fixture( codecept_data_dir( 'licensing' ) );
		}

		$repository = new Feature_Repository(
			new Catalog_Repository( $catalog_client ),
			new Product_Repository( $licensing_client )
		);

		$repository->register_type( 'plugin', Zip::class );
		$repository->register_type( 'flag', Flag::class );
		$repository->register_type( 'theme', Zip::class );

		return $repository;
	}

	/**
	 * Tests get returns a Feature_Collection when catalog and licensing succeed.
	 *
	 * @return void
	 */
	public function test_get_returns_collection(): void {
		$repository = $this->make_repository( 'lw-unified-kad-pro-2026' );

		$result = $repository->get( 'lw-unified-kad-pro-2026', 'example.com' );

		$this->assertInstanceOf( Feature_Collection::class, $result );
		$this->assertGreaterThan( 0, $result->count() );
	}

	/**
	 * Tests that catalog plugin type maps to the Zip Feature subclass.
	 *
	 * @return void
	 */
	public function test_it_maps_plugin_type_to_zip(): void {
		$repository = $this->make_repository( 'lw-unified-kad-pro-2026' );
		$result     = $repository->get( 'lw-unified-kad-pro-2026', 'example.com' );
		$feature    = $result->get( 'kad-blocks-pro' );

		$this->assertInstanceOf( Zip::class, $feature );
		$this->assertSame( 'zip', $feature->get_type() );
	}

	/**
	 * Tests that catalog flag type maps to the Flag Feature subclass.
	 *
	 * @return void
	 */
	public function test_it_maps_flag_type_to_flag(): void {
		$repository = $this->make_repository( 'lw-unified-kad-pro-2026' );
		$result     = $repository->get( 'lw-unified-kad-pro-2026', 'example.com' );
		$feature    = $result->get( 'kad-pattern-hub' );

		$this->assertInstanceOf( Flag::class, $feature );
		$this->assertSame( 'flag', $feature->get_type() );
	}

	/**
	 * Tests is_available is true when the license tier rank meets the minimum.
	 *
	 * kadence-pro (rank 2) meets kadence-basic (rank 1) minimum for kad-blocks-pro.
	 *
	 * @return void
	 */
	public function test_available_when_tier_meets_minimum(): void {
		$repository = $this->make_repository( 'lw-unified-kad-pro-2026' );
		$result     = $repository->get( 'lw-unified-kad-pro-2026', 'example.com' );

		$this->assertTrue(
			$result->get( 'kad-blocks-pro' )->is_available(),
			'Pro tier (rank 2) should meet kadence-basic minimum (rank 1).'
		);
		$this->assertTrue(
			$result->get( 'kad-pattern-hub' )->is_available(),
			'Pro tier (rank 2) should meet kadence-basic minimum (rank 1).'
		);
	}

	/**
	 * Tests is_available is false when the license tier rank is below the minimum.
	 *
	 * kadence-pro (rank 2) does not meet kadence-agency (rank 3) minimum for solid-central.
	 *
	 * @return void
	 */
	public function test_unavailable_when_tier_below_minimum(): void {
		$repository = $this->make_repository( 'lw-unified-kad-pro-2026' );
		$result     = $repository->get( 'lw-unified-kad-pro-2026', 'example.com' );

		$this->assertFalse(
			$result->get( 'solid-central' )->is_available(),
			'Pro tier (rank 2) should not meet kadence-agency minimum (rank 3).'
		);
	}

	/**
	 * Tests all features are unavailable when the licensing API returns a WP_Error.
	 *
	 * @return void
	 */
	public function test_all_unavailable_when_licensing_errors(): void {
		$repository = $this->make_repository();

		$result = $repository->get( 'invalid-key', 'example.com' );

		$this->assertInstanceOf( Feature_Collection::class, $result );

		foreach ( $result as $feature ) {
			$this->assertFalse(
				$feature->is_available(),
				sprintf( 'Feature "%s" should be unavailable when licensing fails.', $feature->get_slug() )
			);
		}
	}

	/**
	 * Tests that a catalog error returns a WP_Error.
	 *
	 * @return void
	 */
	public function test_catalog_error_returns_wp_error(): void {
		$catalog_client = new Catalog_Fixture( '/tmp/does-not-exist-' . uniqid() . '.json' );

		$licensing_client = new Licensing_Fixture( codecept_data_dir( 'licensing' ) );

		$repository = new Feature_Repository(
			new Catalog_Repository( $catalog_client ),
			new Product_Repository( $licensing_client )
		);

		$repository->register_type( 'plugin', Zip::class );
		$repository->register_type( 'flag', Flag::class );

		$result = $repository->get( 'lw-unified-kad-pro-2026', 'example.com' );

		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * Tests that the feature catalog is stored in a WordPress transient after fetching.
	 *
	 * @return void
	 */
	public function test_it_caches_in_transient(): void {
		$repository = $this->make_repository( 'lw-unified-kad-pro-2026' );

		$repository->get( 'lw-unified-kad-pro-2026', 'example.com' );

		$cached = get_transient( Feature_Repository::TRANSIENT_KEY );

		$this->assertInstanceOf( Feature_Collection::class, $cached );
	}

	/**
	 * Tests that a cached transient is returned without calling the clients again.
	 *
	 * @return void
	 */
	public function test_it_returns_cached_collection(): void {
		$cached = new Feature_Collection();
		$cached->add(
			Zip::from_array(
				[
					'slug'              => 'cached-feature',
					'group'             => 'test',
					'tier'              => 'free',
					'name'              => 'Cached',
					'description'       => '',
					'is_available'      => true,
					'documentation_url' => '',
					'plugin_file'       => '',
				]
			)
		);

		set_transient( Feature_Repository::TRANSIENT_KEY, $cached );

		$repository = $this->make_repository( 'lw-unified-kad-pro-2026' );
		$result     = $repository->get( 'lw-unified-kad-pro-2026', 'example.com' );

		$this->assertCount( 1, $result );
		$this->assertSame( 'cached-feature', $result->get( 'cached-feature' )->get_slug() );
	}

	/**
	 * Tests that a cached WP_Error transient is returned directly.
	 *
	 * @return void
	 */
	public function test_it_returns_cached_wp_error(): void {
		$error = new WP_Error( 'api_error', 'Cached error' );
		set_transient( Feature_Repository::TRANSIENT_KEY, $error );

		$repository = $this->make_repository( 'lw-unified-kad-pro-2026' );
		$result     = $repository->get( 'lw-unified-kad-pro-2026', 'example.com' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Cached error', $result->get_error_message() );
	}

	/**
	 * Tests refresh clears and re-fetches the transient cache.
	 *
	 * @return void
	 */
	public function test_refresh_clears_and_refetches(): void {
		$repository = $this->make_repository( 'lw-unified-kad-pro-2026' );

		$repository->get( 'lw-unified-kad-pro-2026', 'example.com' );

		$this->assertInstanceOf(
			Feature_Collection::class,
			get_transient( Feature_Repository::TRANSIENT_KEY )
		);

		$repository->refresh( 'lw-unified-kad-pro-2026', 'example.com' );

		$this->assertInstanceOf(
			Feature_Collection::class,
			get_transient( Feature_Repository::TRANSIENT_KEY )
		);
	}

	/**
	 * Tests that hydrate_feature returns a WP_Error for unregistered catalog types.
	 *
	 * @return void
	 */
	public function test_hydrate_feature_returns_wp_error_for_unknown_type(): void {
		$repository = $this->make_repository( 'lw-unified-kad-pro-2026' );

		// Do NOT register 'unknown_type' — only plugin/flag/theme are registered.
		$catalog_feature = Catalog_Feature::from_array(
			[
				'feature_slug'      => 'test-feature',
				'type'              => 'unknown_type',
				'minimum_tier'      => 'kadence-basic',
				'name'              => 'Test Feature',
				'description'       => 'A feature with an unknown type.',
				'documentation_url' => '',
			]
		);

		$tiers = new Tier_Collection();
		$tiers->add(
			Catalog_Tier::from_array(
				[
					'tier_slug' => 'kadence-basic',
					'rank'      => 1,
				] 
			) 
		);

		$product = new Product_Catalog( 'kadence', $tiers, [ $catalog_feature ] );

		$method = new ReflectionMethod( Feature_Repository::class, 'hydrate_feature' );

		$result = $method->invoke( $repository, $catalog_feature, $product, 1 );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( Error_Code::UNKNOWN_FEATURE_TYPE, $result->get_error_code() );
		$this->assertStringContainsString( 'unknown_type', $result->get_error_message() );
		$this->assertStringContainsString( 'test-feature', $result->get_error_message() );
	}

	/**
	 * Tests that hydrate_feature returns a Feature for registered types.
	 *
	 * @return void
	 */
	public function test_hydrate_feature_returns_feature_for_known_type(): void {
		$repository = $this->make_repository( 'lw-unified-kad-pro-2026' );

		$catalog_feature = Catalog_Feature::from_array(
			[
				'feature_slug'      => 'test-flag',
				'type'              => 'flag',
				'minimum_tier'      => 'kadence-basic',
				'name'              => 'Test Flag',
				'description'       => 'A flag feature.',
				'documentation_url' => '',
			]
		);

		$tiers = new Tier_Collection();
		$tiers->add(
			Catalog_Tier::from_array(
				[
					'tier_slug' => 'kadence-basic',
					'rank'      => 1,
				] 
			) 
		);

		$product = new Product_Catalog( 'kadence', $tiers, [ $catalog_feature ] );

		$method = new ReflectionMethod( Feature_Repository::class, 'hydrate_feature' );

		$result = $method->invoke( $repository, $catalog_feature, $product, 1 );

		$this->assertInstanceOf( Flag::class, $result );
		$this->assertSame( 'test-flag', $result->get_slug() );
	}

	/**
	 * Tests that feature data fields are correctly mapped from catalog to feature.
	 *
	 * @return void
	 */
	public function test_it_maps_feature_data_correctly(): void {
		$repository = $this->make_repository( 'lw-unified-kad-pro-2026' );
		$result     = $repository->get( 'lw-unified-kad-pro-2026', 'example.com' );
		$feature    = $result->get( 'kad-blocks-pro' );

		$this->assertSame( 'kad-blocks-pro', $feature->get_slug() );
		$this->assertSame( 'kadence', $feature->get_group() );
		$this->assertSame( 'kadence-basic', $feature->get_tier() );
		$this->assertSame( 'Blocks Pro', $feature->get_name() );
		$this->assertSame( 'Premium Gutenberg blocks for advanced page building.', $feature->get_description() );
		$this->assertSame( 'https://www.kadencewp.com/help-center/', $feature->get_documentation_url() );

		$this->assertInstanceOf( Zip::class, $feature );
		$this->assertSame( 'kadence-blocks-pro/kadence-blocks-pro.php', $feature->get_plugin_file() );
	}
}
