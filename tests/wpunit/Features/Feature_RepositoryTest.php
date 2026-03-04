<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Features;

use StellarWP\Uplink\Catalog\Catalog_Collection;
use StellarWP\Uplink\Catalog\Catalog_Repository;
use StellarWP\Uplink\Catalog\Contracts\Catalog_Client;
use StellarWP\Uplink\Catalog\Results\Catalog_Feature;
use StellarWP\Uplink\Catalog\Results\Catalog_Tier;
use StellarWP\Uplink\Catalog\Results\Product_Catalog;
use StellarWP\Uplink\Catalog\Results\Tier_Collection;
use StellarWP\Uplink\Features\Feature_Collection;
use StellarWP\Uplink\Features\Feature_Repository;
use StellarWP\Uplink\Features\Types\Built_In;
use StellarWP\Uplink\Features\Types\Zip;
use StellarWP\Uplink\Licensing\Contracts\Licensing_Client;
use StellarWP\Uplink\Licensing\Product_Repository;
use StellarWP\Uplink\Licensing\Results\Product_Entry;
use StellarWP\Uplink\Tests\UplinkTestCase;
use WP_Error;

final class Feature_RepositoryTest extends UplinkTestCase {

	/**
	 * License key used for testing.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	private const KEY = 'test-license-key';

	/**
	 * Site domain used for testing.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	private const DOMAIN = 'example.com';

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
	 * Builds a Catalog_Collection with a single product containing tiers and features.
	 *
	 * Product: kadence
	 * Tiers: kadence-starter (rank 1), kadence-pro (rank 2)
	 * Features: kadence-blocks-pro (plugin, min kadence-pro), advanced-headers (flag, min kadence-starter)
	 *
	 * @return Catalog_Collection
	 */
	private function build_catalog(): Catalog_Collection {
		$tiers = new Tier_Collection();

		$tiers->add(
			Catalog_Tier::from_array(
				[
					'slug'         => 'kadence-starter',
					'name'         => 'Starter',
					'rank'         => 1,
					'purchase_url' => '',
				]
			)
		);

		$tiers->add(
			Catalog_Tier::from_array(
				[
					'slug'         => 'kadence-pro',
					'name'         => 'Pro',
					'rank'         => 2,
					'purchase_url' => '',
				]
			)
		);

		$features = [
			Catalog_Feature::from_array(
				[
					'feature_slug'      => 'kadence-blocks-pro',
					'type'              => 'plugin',
					'minimum_tier'      => 'kadence-pro',
					'plugin_file'       => 'kadence-blocks-pro/kadence-blocks-pro.php',
					'name'              => 'Kadence Blocks Pro',
					'description'       => 'Pro blocks extension',
					'documentation_url' => 'https://example.com/docs',
				]
			),
			Catalog_Feature::from_array(
				[
					'feature_slug'      => 'advanced-headers',
					'type'              => 'flag',
					'minimum_tier'      => 'kadence-starter',
					'name'              => 'Advanced Headers',
					'description'       => 'Advanced header features',
					'documentation_url' => 'https://example.com/headers',
				]
			),
		];

		$product    = new Product_Catalog( 'kadence', $tiers, $features );
		$collection = new Catalog_Collection();
		$collection->add( $product );

		return $collection;
	}

	/**
	 * Builds a licensing Product_Entry for a given tier.
	 *
	 * @param string $tier The bare tier name (e.g. 'pro', 'starter').
	 *
	 * @return Product_Entry
	 */
	private function build_license( string $tier ): Product_Entry {
		return new Product_Entry(
			[
				'product_slug'      => 'kadence',
				'tier'              => $tier,
				'pending_tier'      => null,
				'status'            => 'active',
				'expires'           => '2027-01-01 00:00:00',
				'site_limit'        => 0,
				'active_count'      => 1,
				'installed_here'    => true,
				'validation_status' => null,
			]
		);
	}

	/**
	 * Creates a Feature_Repository with mocked catalog and licensing clients.
	 *
	 * @param Catalog_Collection|WP_Error $catalog_result  Return value for Catalog_Client::get_catalog().
	 * @param Product_Entry[]|WP_Error    $licensing_result Return value for Licensing_Client::get_products().
	 *
	 * @return Feature_Repository
	 */
	private function make_repository( $catalog_result, $licensing_result ): Feature_Repository {
		$catalog_client = $this->makeEmpty(
			Catalog_Client::class,
			[ 'get_catalog' => $catalog_result ]
		);

		$licensing_client = $this->makeEmpty(
			Licensing_Client::class,
			[ 'get_products' => $licensing_result ]
		);

		return new Feature_Repository(
			new Catalog_Repository( $catalog_client ),
			new Product_Repository( $licensing_client )
		);
	}

	/**
	 * Tests get returns a Feature_Collection when catalog and licensing succeed.
	 *
	 * @return void
	 */
	public function test_get_returns_collection(): void {
		$license    = $this->build_license( 'pro' );
		$repository = $this->make_repository( $this->build_catalog(), [ $license ] );

		$result = $repository->get( self::KEY, self::DOMAIN );

		$this->assertInstanceOf( Feature_Collection::class, $result );
		$this->assertSame( 2, $result->count() );
	}

	/**
	 * Tests that catalog plugin type maps to the Zip Feature subclass.
	 *
	 * @return void
	 */
	public function test_it_maps_plugin_type_to_zip(): void {
		$license    = $this->build_license( 'pro' );
		$repository = $this->make_repository( $this->build_catalog(), [ $license ] );

		$result  = $repository->get( self::KEY, self::DOMAIN );
		$feature = $result->get( 'kadence-blocks-pro' );

		$this->assertInstanceOf( Zip::class, $feature );
		$this->assertSame( 'zip', $feature->get_type() );
	}

	/**
	 * Tests that catalog flag type maps to the Built_In Feature subclass.
	 *
	 * @return void
	 */
	public function test_it_maps_flag_type_to_built_in(): void {
		$license    = $this->build_license( 'pro' );
		$repository = $this->make_repository( $this->build_catalog(), [ $license ] );

		$result  = $repository->get( self::KEY, self::DOMAIN );
		$feature = $result->get( 'advanced-headers' );

		$this->assertInstanceOf( Built_In::class, $feature );
		$this->assertSame( 'built_in', $feature->get_type() );
	}

	/**
	 * Tests is_available is true when the license tier rank meets the minimum.
	 *
	 * @return void
	 */
	public function test_available_when_tier_meets_minimum(): void {
		$license    = $this->build_license( 'pro' );
		$repository = $this->make_repository( $this->build_catalog(), [ $license ] );

		$result = $repository->get( self::KEY, self::DOMAIN );

		$this->assertTrue(
			$result->get( 'kadence-blocks-pro' )->is_available(),
			'Pro tier (rank 2) should meet kadence-pro minimum (rank 2).'
		);
		$this->assertTrue(
			$result->get( 'advanced-headers' )->is_available(),
			'Pro tier (rank 2) should meet kadence-starter minimum (rank 1).'
		);
	}

	/**
	 * Tests is_available is false when the license tier rank is below the minimum.
	 *
	 * @return void
	 */
	public function test_unavailable_when_tier_below_minimum(): void {
		$license    = $this->build_license( 'starter' );
		$repository = $this->make_repository( $this->build_catalog(), [ $license ] );

		$result = $repository->get( self::KEY, self::DOMAIN );

		$this->assertFalse(
			$result->get( 'kadence-blocks-pro' )->is_available(),
			'Starter tier (rank 1) should not meet kadence-pro minimum (rank 2).'
		);
		$this->assertTrue(
			$result->get( 'advanced-headers' )->is_available(),
			'Starter tier (rank 1) should meet kadence-starter minimum (rank 1).'
		);
	}

	/**
	 * Tests all features are unavailable when the licensing API returns a WP_Error.
	 *
	 * @return void
	 */
	public function test_all_unavailable_when_licensing_errors(): void {
		$error      = new WP_Error( 'license_error', 'Licensing API failed.' );
		$repository = $this->make_repository( $this->build_catalog(), $error );

		$result = $repository->get( self::KEY, self::DOMAIN );

		$this->assertInstanceOf( Feature_Collection::class, $result );
		$this->assertFalse(
			$result->get( 'kadence-blocks-pro' )->is_available(),
			'Feature should be unavailable when licensing fails.'
		);
		$this->assertFalse(
			$result->get( 'advanced-headers' )->is_available(),
			'Feature should be unavailable when licensing fails.'
		);
	}

	/**
	 * Tests that a catalog error returns a WP_Error.
	 *
	 * @return void
	 */
	public function test_catalog_error_returns_wp_error(): void {
		$error      = new WP_Error( 'catalog_error', 'Catalog API failed.' );
		$repository = $this->make_repository( $error, [] );

		$result = $repository->get( self::KEY, self::DOMAIN );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Catalog API failed.', $result->get_error_message() );
	}

	/**
	 * Tests that the feature catalog is stored in a WordPress transient after fetching.
	 *
	 * @return void
	 */
	public function test_it_caches_in_transient(): void {
		$license    = $this->build_license( 'pro' );
		$repository = $this->make_repository( $this->build_catalog(), [ $license ] );

		$repository->get( self::KEY, self::DOMAIN );

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

		$repository = $this->make_repository( $this->build_catalog(), [] );
		$result     = $repository->get( self::KEY, self::DOMAIN );

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

		$repository = $this->make_repository( $this->build_catalog(), [] );
		$result     = $repository->get( self::KEY, self::DOMAIN );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Cached error', $result->get_error_message() );
	}

	/**
	 * Tests refresh clears and re-fetches the transient cache.
	 *
	 * @return void
	 */
	public function test_refresh_clears_and_refetches(): void {
		$license    = $this->build_license( 'pro' );
		$repository = $this->make_repository( $this->build_catalog(), [ $license ] );

		$repository->get( self::KEY, self::DOMAIN );

		$this->assertInstanceOf(
			Feature_Collection::class,
			get_transient( Feature_Repository::TRANSIENT_KEY )
		);

		$repository->refresh( self::KEY, self::DOMAIN );

		$this->assertInstanceOf(
			Feature_Collection::class,
			get_transient( Feature_Repository::TRANSIENT_KEY )
		);
	}

	/**
	 * Tests that feature data fields are correctly mapped from catalog to feature.
	 *
	 * @return void
	 */
	public function test_it_maps_feature_data_correctly(): void {
		$license    = $this->build_license( 'pro' );
		$repository = $this->make_repository( $this->build_catalog(), [ $license ] );

		$result  = $repository->get( self::KEY, self::DOMAIN );
		$feature = $result->get( 'kadence-blocks-pro' );

		$this->assertSame( 'kadence-blocks-pro', $feature->get_slug() );
		$this->assertSame( 'kadence', $feature->get_group() );
		$this->assertSame( 'kadence-pro', $feature->get_tier() );
		$this->assertSame( 'Kadence Blocks Pro', $feature->get_name() );
		$this->assertSame( 'Pro blocks extension', $feature->get_description() );
		$this->assertSame( 'https://example.com/docs', $feature->get_documentation_url() );

		$this->assertInstanceOf( Zip::class, $feature );
		$this->assertSame( 'kadence-blocks-pro/kadence-blocks-pro.php', $feature->get_plugin_file() );
	}
}
