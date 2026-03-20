<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Features;

use ReflectionMethod;
use StellarWP\Uplink\Catalog\Catalog_Repository;
use StellarWP\Uplink\Catalog\Clients\Fixture_Client as Catalog_Fixture;
use StellarWP\Uplink\Catalog\Results\Catalog_Feature;
use StellarWP\Uplink\Catalog\Results\Catalog_Tier;
use StellarWP\Uplink\Catalog\Results\Product_Catalog;
use StellarWP\Uplink\Catalog\Results\Tier_Collection;
use StellarWP\Uplink\Features\Error_Code;
use StellarWP\Uplink\Features\Feature_Collection;
use StellarWP\Uplink\Features\Feature_Repository;
use StellarWP\Uplink\Features\Resolve_Feature_Collection;
use StellarWP\Uplink\Features\Types\Flag;
use StellarWP\Uplink\Features\Types\Plugin;
use StellarWP\Uplink\Licensing\Clients\Licensing_Client;
use StellarWP\Uplink\Licensing\Clients\Fixture_Client as Licensing_Fixture;
use StellarWP\Uplink\Licensing\License_Manager;
use StellarWP\Uplink\Licensing\Registry\Product_Registry;
use StellarWP\Uplink\Licensing\Repositories\License_Repository;
use StellarWP\Uplink\Tests\UplinkTestCase;
use WP_Error;

final class Feature_RepositoryTest extends UplinkTestCase {

	/**
	 * Clears upstream caches before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		delete_option( Catalog_Repository::CATALOG_STATE_OPTION_NAME );
		delete_option( License_Repository::PRODUCTS_STATE_OPTION_NAME );
	}

	/**
	 * Clears upstream caches after each test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		delete_option( Catalog_Repository::CATALOG_STATE_OPTION_NAME );
		delete_option( License_Repository::PRODUCTS_STATE_OPTION_NAME );

		parent::tearDown();
	}

	/**
	 * Creates a Resolve_Feature_Collection with the given repository dependencies.
	 *
	 * @param Catalog_Repository $catalog  The catalog repository.
	 * @param License_Manager    $licensing The licensing manager.
	 *
	 * @return Resolve_Feature_Collection
	 */
	private function make_resolver(
		Catalog_Repository $catalog,
		License_Manager $licensing
	): Resolve_Feature_Collection {
		$site_data = $this->makeEmpty( \StellarWP\Uplink\Site\Data::class, [ 'get_domain' => 'example.com' ] );
		$resolver  = new Resolve_Feature_Collection( $catalog, $licensing, $site_data );

		$resolver->register_type( 'plugin', Plugin::class );
		$resolver->register_type( 'flag', Flag::class );
		$resolver->register_type( 'theme', Plugin::class );

		return $resolver;
	}

	/**
	 * Creates a Feature_Repository backed by the real fixture files.
	 *
	 * @param string|null $licensing_override Optional. Pass a key for the Licensing_Fixture,
	 *                                        or null to use no stored key.
	 *
	 * @return Feature_Repository
	 */
	private function make_repository( ?string $licensing_override = null ): Feature_Repository {
		$catalog_client   = new Catalog_Fixture( codecept_data_dir( 'catalog/default.json' ) );
		$licensing_client = new Licensing_Fixture( codecept_data_dir( 'licensing' ) );

		$catalog   = new Catalog_Repository( $catalog_client );
		$licensing = new License_Manager( new License_Repository(), new Product_Registry(), $licensing_client );

		if ( $licensing_override !== null ) {
			$licensing->store_key( $licensing_override );
		}

		return new Feature_Repository(
			$this->make_resolver( $catalog, $licensing )
		);
	}

	/**
	 * Creates a Feature_Repository with a stored key but a licensing client that always fails.
	 *
	 * @return Feature_Repository
	 */
	private function make_error_repository(): Feature_Repository {
		$catalog_client   = new Catalog_Fixture( codecept_data_dir( 'catalog/default.json' ) );
		$licensing_client = $this->makeEmpty(
			Licensing_Client::class,
			[ 'get_products' => new WP_Error( 'license_error', 'Licensing failed.' ) ]
		);

		$catalog   = new Catalog_Repository( $catalog_client );
		$licensing = new License_Manager( new License_Repository(), new Product_Registry(), $licensing_client );
		$licensing->store_key( 'LWSW-test-error-key' );

		return new Feature_Repository(
			$this->make_resolver( $catalog, $licensing )
		);
	}

	/**
	 * Tests get returns a Feature_Collection when catalog and licensing succeed.
	 *
	 * @return void
	 */
	public function test_get_returns_collection(): void {
		$repository = $this->make_repository( 'lwsw-unified-kad-pro-2026' );

		$result = $repository->get();

		$this->assertInstanceOf( Feature_Collection::class, $result );
		$this->assertGreaterThan( 0, $result->count() );
	}

	/**
	 * Tests that catalog plugin type maps to the Plugin Feature subclass.
	 *
	 * @return void
	 */
	public function test_it_maps_plugin_type_to_plugin(): void {
		$repository = $this->make_repository( 'lwsw-unified-kad-pro-2026' );
		$result     = $repository->get();
		$feature    = $result->get( 'kad-blocks-pro' );

		$this->assertInstanceOf( Plugin::class, $feature );
		$this->assertSame( 'plugin', $feature->get_type() );
	}

	/**
	 * Tests that catalog flag type maps to the Flag Feature subclass.
	 *
	 * @return void
	 */
	public function test_it_maps_flag_type_to_flag(): void {
		$repository = $this->make_repository( 'lwsw-unified-kad-pro-2026' );
		$result     = $repository->get();
		$feature    = $result->get( 'kad-pattern-hub' );

		$this->assertInstanceOf( Flag::class, $feature );
		$this->assertSame( 'flag', $feature->get_type() );
	}

	/**
	 * Tests is_available is true when the feature slug is in the license capabilities.
	 *
	 * kadence-pro capabilities include kad-blocks-pro and kad-pattern-hub.
	 *
	 * @return void
	 */
	public function test_available_when_tier_meets_minimum(): void {
		$repository = $this->make_repository( 'lwsw-unified-kad-pro-2026' );
		$result     = $repository->get();

		$this->assertTrue(
			$result->get( 'kad-blocks-pro' )->is_available(),
			'kad-blocks-pro should be available — it is in the kadence-pro capabilities.'
		);
		$this->assertTrue(
			$result->get( 'kad-pattern-hub' )->is_available(),
			'kad-pattern-hub should be available — it is in the kadence-pro capabilities.'
		);
	}

	/**
	 * Tests is_available is false when the feature slug is not in the license capabilities.
	 *
	 * kadence-pro capabilities do not include solid-central (an agency-tier feature).
	 *
	 * @return void
	 */
	public function test_unavailable_when_tier_below_minimum(): void {
		$repository = $this->make_repository( 'lwsw-unified-kad-pro-2026' );
		$result     = $repository->get();

		$this->assertFalse(
			$result->get( 'solid-central' )->is_available(),
			'solid-central should not be available — it is not in the kadence-pro capabilities.'
		);
	}

	/**
	 * Tests that a licensing API error (with a stored key) returns a WP_Error.
	 *
	 * @return void
	 */
	public function test_licensing_error_returns_wp_error(): void {
		$repository = $this->make_error_repository();

		$result = $repository->get();

		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * Tests that feature resolution succeeds and returns a Feature_Collection when no license key is stored.
	 *
	 * Free-tier features (rank 0) are available without a license. Paid-tier features are not.
	 *
	 * @return void
	 */
	public function test_it_resolves_catalog_without_license_key(): void {
		$repository = $this->make_repository();

		$result = $repository->get();

		$this->assertInstanceOf( Feature_Collection::class, $result );
		$this->assertGreaterThan( 0, $result->count() );

		// Free-tier features (minimum tier rank 0) are available without a license.
		$this->assertTrue(
			$result->get( 'kadence-blocks' )->is_available(),
			'Free-tier plugin should be available without a license.'
		);
		$this->assertTrue(
			$result->get( 'kadence' )->is_available(),
			'Free-tier theme should be available without a license.'
		);

		// Paid-tier features are not available without a license.
		$this->assertFalse(
			$result->get( 'kad-blocks-pro' )->is_available(),
			'Basic-tier feature should not be available without a license.'
		);
		$this->assertFalse(
			$result->get( 'solid-central' )->is_available(),
			'Agency-tier feature should not be available without a license.'
		);
	}

	/**
	 * Tests that free-tier features resolve as available for unlicensed users.
	 *
	 * Without a license there is no capabilities array, so the resolver falls back
	 * to making only features with minimum tier rank 0 available.
	 *
	 * @return void
	 */
	public function test_free_tier_features_available_without_license(): void {
		$repository = $this->make_repository();

		$result = $repository->get();

		$this->assertInstanceOf( Feature_Collection::class, $result );

		$free_features = $result->filter( null, 'kadence-free' );

		$this->assertGreaterThan( 0, $free_features->count() );

		foreach ( $free_features as $feature ) {
			$this->assertTrue(
				$feature->is_available(),
				sprintf( 'Free-tier feature "%s" should be available without a license.', $feature->get_slug() )
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

		$catalog   = new Catalog_Repository( $catalog_client );
		$licensing = new License_Manager( new License_Repository(), new Product_Registry(), $licensing_client );
		$licensing->store_key( 'lwsw-unified-kad-pro-2026' );

		$repository = new Feature_Repository(
			$this->make_resolver( $catalog, $licensing )
		);

		$result = $repository->get();

		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * Tests that the same instance is returned on repeated calls within one request.
	 *
	 * @return void
	 */
	public function test_it_returns_cached_collection_within_request(): void {
		$repository = $this->make_repository( 'lwsw-unified-kad-pro-2026' );

		$first  = $repository->get();
		$second = $repository->get();

		$this->assertSame( $first, $second );
	}

	/**
	 * Tests refresh clears the in-memory cache and re-resolves.
	 *
	 * @return void
	 */
	public function test_refresh_clears_and_refetches(): void {
		$repository = $this->make_repository( 'lwsw-unified-kad-pro-2026' );

		$first  = $repository->get();
		$second = $repository->refresh();

		$this->assertInstanceOf( Feature_Collection::class, $first );
		$this->assertInstanceOf( Feature_Collection::class, $second );
		$this->assertNotSame( $first, $second );
	}

	/**
	 * Tests that hydrate_feature returns a WP_Error for unregistered catalog types.
	 *
	 * @return void
	 */
	public function test_hydrate_feature_returns_wp_error_for_unknown_type(): void {
		$resolver = $this->make_resolver(
			new Catalog_Repository( new Catalog_Fixture( codecept_data_dir( 'catalog/default.json' ) ) ),
			new License_Manager( new License_Repository(), new Product_Registry(), new Licensing_Fixture( codecept_data_dir( 'licensing' ) ) )
		);

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

		$method = new ReflectionMethod( Resolve_Feature_Collection::class, 'hydrate_feature' );
		$method->setAccessible( true ); // Required for PHP < 8.1.

		$result = $method->invoke( $resolver, $catalog_feature, $product, null );

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
		$resolver = $this->make_resolver(
			new Catalog_Repository( new Catalog_Fixture( codecept_data_dir( 'catalog/default.json' ) ) ),
			new License_Manager( new License_Repository(), new Product_Registry(), new Licensing_Fixture( codecept_data_dir( 'licensing' ) ) )
		);

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

		$method = new ReflectionMethod( Resolve_Feature_Collection::class, 'hydrate_feature' );
		$method->setAccessible( true ); // Required for PHP < 8.1.

		$result = $method->invoke( $resolver, $catalog_feature, $product, [ 'test-flag' ] );

		$this->assertInstanceOf( Flag::class, $result );
		$this->assertSame( 'test-flag', $result->get_slug() );
	}

	/**
	 * Tests that feature data fields are correctly mapped from catalog to feature.
	 *
	 * @return void
	 */
	public function test_it_maps_feature_data_correctly(): void {
		$repository = $this->make_repository( 'lwsw-unified-kad-pro-2026' );
		$result     = $repository->get();
		$feature    = $result->get( 'kad-blocks-pro' );

		$this->assertSame( 'kad-blocks-pro', $feature->get_slug() );
		$this->assertSame( 'kadence', $feature->get_product() );
		$this->assertSame( 'kadence-basic', $feature->get_tier() );
		$this->assertSame( 'Blocks Pro', $feature->get_name() );
		$this->assertSame( 'Premium Gutenberg blocks for advanced page building.', $feature->get_description() );
		$this->assertSame( 'https://www.kadencewp.com/help-center/', $feature->get_documentation_url() );

		$this->assertInstanceOf( Plugin::class, $feature );
		$this->assertSame( 'kadence-blocks-pro/kadence-blocks-pro.php', $feature->get_plugin_file() );
	}
}
