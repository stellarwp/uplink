<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Licensing\Repositories;

use StellarWP\Uplink\Catalog\Catalog_Repository;
use StellarWP\Uplink\Catalog\Fixture_Client as Catalog_Fixture;
use StellarWP\Uplink\Features\Feature_Repository;
use StellarWP\Uplink\Features\Resolve_Feature_Collection;
use StellarWP\Uplink\Features\Update\Resolve_Update_Data;
use StellarWP\Uplink\Features\Update\Update_Repository;
use StellarWP\Uplink\Features\Types\Flag;
use StellarWP\Uplink\Features\Types\Plugin;
use StellarWP\Uplink\Licensing\Fixture_Client as Licensing_Fixture;
use StellarWP\Uplink\Licensing\License_Manager;
use StellarWP\Uplink\Licensing\Product_Collection;
use StellarWP\Uplink\Licensing\Registry\Product_Registry;
use StellarWP\Uplink\Licensing\Repositories\License_Repository;
use StellarWP\Uplink\Tests\UplinkTestCase;

/**
 * Integration tests verifying that changing the unified license key
 * invalidates all repository transient caches, causing fresh data
 * to be fetched on the next call.
 *
 * @since 3.0.0
 */
final class License_Key_Cache_InvalidationTest extends UplinkTestCase {

	private License_Repository $license_repository;
	private License_Manager $license_manager;
	private Catalog_Repository $catalog_repository;
	private Feature_Repository $feature_repository;
	private Update_Repository $update_repository;

	protected function setUp(): void {
		parent::setUp();

		$this->license_repository = new License_Repository();

		$licensing_client      = new Licensing_Fixture( codecept_data_dir( 'licensing' ) );
		$this->license_manager = new License_Manager(
			$this->license_repository,
			new Product_Registry(),
			$licensing_client
		);

		$catalog_client           = new Catalog_Fixture( codecept_data_dir( 'catalog/default.json' ) );
		$this->catalog_repository = new Catalog_Repository( $catalog_client );

		$resolver = new Resolve_Feature_Collection(
			$this->catalog_repository,
			$this->license_manager
		);
		$resolver->register_type( 'plugin', Plugin::class );
		$resolver->register_type( 'flag', Flag::class );
		$resolver->register_type( 'theme', Plugin::class ); // TODO: Will be replaced with Theme type.

		$this->feature_repository = new Feature_Repository( $resolver );

		$update_resolver          = new Resolve_Update_Data( $this->feature_repository, $this->catalog_repository );
		$this->update_repository  = new Update_Repository( $update_resolver );

		// Register cache invalidation hooks that providers normally wire up.
		add_action(
			'stellarwp/uplink/unified_license_key_changed',
			[ $this->license_repository, 'delete_products' ]
		);
		add_action(
			'stellarwp/uplink/unified_license_key_changed',
			static function () {
				delete_transient( Catalog_Repository::TRANSIENT_KEY );
			}
		);
		add_action(
			'stellarwp/uplink/unified_license_key_changed',
			static function () {
				delete_transient( Feature_Repository::TRANSIENT_KEY );
			}
		);
		add_action(
			'stellarwp/uplink/unified_license_key_changed',
			static function () {
				delete_transient( Update_Repository::TRANSIENT_KEY );
			}
		);

		delete_transient( License_Repository::PRODUCTS_TRANSIENT_KEY );
		delete_transient( Catalog_Repository::TRANSIENT_KEY );
		delete_transient( Feature_Repository::TRANSIENT_KEY );
		delete_transient( Update_Repository::TRANSIENT_KEY );
		delete_option( License_Repository::KEY_OPTION_NAME );
	}

	protected function tearDown(): void {
		remove_all_filters( 'stellarwp/uplink/unified_license_key_changed' );

		delete_transient( License_Repository::PRODUCTS_TRANSIENT_KEY );
		delete_transient( Catalog_Repository::TRANSIENT_KEY );
		delete_transient( Feature_Repository::TRANSIENT_KEY );
		delete_transient( Update_Repository::TRANSIENT_KEY );
		delete_option( License_Repository::KEY_OPTION_NAME );

		parent::tearDown();
	}

	public function test_license_manager_returns_fresh_data_after_key_change(): void {
		$this->license_repository->store_key( 'LWSW-UNIFIED-PRO-2026' );
		$pro_result = $this->license_manager->get_products( 'example.com' );

		$this->assertSame( 'kadence-pro', $pro_result->get( 'kadence' )->get_tier() );
		$this->assertSame( 'give-pro', $pro_result->get( 'give' )->get_tier() );

		// Cached — calling again returns stale pro data.
		$stale = $this->license_manager->get_products( 'example.com' );
		$this->assertSame( 'kadence-pro', $stale->get( 'kadence' )->get_tier(), 'Should still be cached pro data.' );

		// Change the license key — fires action, invalidates the transient.
		$this->license_repository->store_key( 'LWSW-UNIFIED-BASIC-2026' );

		$basic_result = $this->license_manager->get_products( 'example.com' );

		$this->assertSame( 'kadence-basic', $basic_result->get( 'kadence' )->get_tier() );
		$this->assertSame( 'give-basic', $basic_result->get( 'give' )->get_tier() );
	}

	public function test_catalog_repository_fetches_fresh_after_key_change(): void {
		// Seed a stale catalog array with a single fake product.
		$stale = [
			[
				'product_slug' => 'stale-product',
				'tiers'        => [],
				'features'     => [],
			],
		];
		set_transient( Catalog_Repository::TRANSIENT_KEY, $stale );

		// Confirm the stale cache is served.
		$cached = $this->catalog_repository->get();
		$this->assertCount( 1, $cached );
		$this->assertNotNull( $cached->get( 'stale-product' ) );

		// Change the license key — invalidates the catalog transient.
		$this->license_repository->store_key( 'LWSW-UNIFIED-BASIC-2026' );

		// Fresh fetch should return the real catalog, not the stale single-product one.
		$fresh = $this->catalog_repository->get();
		$this->assertGreaterThan( 1, count( $fresh ) );
		$this->assertNull( $fresh->get( 'stale-product' ) );
		$this->assertNotNull( $fresh->get( 'kadence' ) );
	}

	public function test_feature_repository_returns_different_availability_after_key_change(): void {
		// With a pro key, pro-tier features like kad-shop-kit (min: kadence-pro) are available.
		$this->license_repository->store_key( 'LWSW-UNIFIED-KAD-PRO-2026' );
		$pro_result = $this->feature_repository->get( 'LWSW-UNIFIED-KAD-PRO-2026', 'example.com' );

		$this->assertTrue(
			$pro_result->get( 'kad-shop-kit' )->is_available(),
			'Pro tier (rank 2) should have access to kad-shop-kit (min: kadence-pro, rank 2).'
		);
		$this->assertTrue(
			$pro_result->get( 'kad-blocks-pro' )->is_available(),
			'Pro tier (rank 2) should have access to kad-blocks-pro (min: kadence-basic, rank 1).'
		);

		// Cached — even after changing stored key, stale pro features are returned until cache clears.
		$stale = $this->feature_repository->get( 'LWSW-UNIFIED-BASIC-2026', 'example.com' );
		$this->assertTrue(
			$stale->get( 'kad-shop-kit' )->is_available(),
			'Should still be cached pro feature data.'
		);

		// Change the license key — invalidates the feature transient.
		$this->license_repository->store_key( 'LWSW-UNIFIED-BASIC-2026' );

		// With a basic key, pro-tier features should no longer be available.
		$basic_result = $this->feature_repository->get( 'LWSW-UNIFIED-BASIC-2026', 'example.com' );

		$this->assertFalse(
			$basic_result->get( 'kad-shop-kit' )->is_available(),
			'Basic tier (rank 1) should NOT have access to kad-shop-kit (min: kadence-pro, rank 2).'
		);
		$this->assertTrue(
			$basic_result->get( 'kad-blocks-pro' )->is_available(),
			'Basic tier (rank 1) should still have access to kad-blocks-pro (min: kadence-basic, rank 1).'
		);
	}

	public function test_all_caches_invalidated_on_key_change(): void {
		// Populate all four caches.
		$this->license_repository->store_key( 'LWSW-UNIFIED-PRO-2026' );
		$this->license_manager->get_products( 'example.com' );
		$this->catalog_repository->get();
		$this->feature_repository->get( 'LWSW-UNIFIED-KAD-PRO-2026', 'example.com' );
		$this->update_repository->get( 'LWSW-UNIFIED-KAD-PRO-2026', 'example.com' );

		$this->assertInstanceOf( Product_Collection::class, $this->license_repository->get_products() );
		$this->assertNotFalse( get_transient( Catalog_Repository::TRANSIENT_KEY ) );
		$this->assertNotFalse( get_transient( Feature_Repository::TRANSIENT_KEY ) );
		$this->assertNotFalse( get_transient( Update_Repository::TRANSIENT_KEY ) );

		// Storing a new key should clear all four transients.
		$this->license_repository->store_key( 'LWSW-UNIFIED-BASIC-2026' );

		$this->assertNull( $this->license_repository->get_products() );
		$this->assertFalse( get_transient( Catalog_Repository::TRANSIENT_KEY ) );
		$this->assertFalse( get_transient( Feature_Repository::TRANSIENT_KEY ) );
		$this->assertFalse( get_transient( Update_Repository::TRANSIENT_KEY ) );
	}

	public function test_all_caches_invalidated_on_key_delete(): void {
		$this->license_repository->store_key( 'LWSW-UNIFIED-PRO-2026' );

		// Populate all four caches.
		$this->license_manager->get_products( 'example.com' );
		$this->catalog_repository->get();
		$this->feature_repository->get( 'LWSW-UNIFIED-KAD-PRO-2026', 'example.com' );
		$this->update_repository->get( 'LWSW-UNIFIED-KAD-PRO-2026', 'example.com' );

		$this->assertInstanceOf( Product_Collection::class, $this->license_repository->get_products() );
		$this->assertNotFalse( get_transient( Catalog_Repository::TRANSIENT_KEY ) );
		$this->assertNotFalse( get_transient( Feature_Repository::TRANSIENT_KEY ) );
		$this->assertNotFalse( get_transient( Update_Repository::TRANSIENT_KEY ) );

		// Deleting the key should clear all four transients.
		$this->license_repository->delete_key();

		$this->assertNull( $this->license_repository->get_products() );
		$this->assertFalse( get_transient( Catalog_Repository::TRANSIENT_KEY ) );
		$this->assertFalse( get_transient( Feature_Repository::TRANSIENT_KEY ) );
		$this->assertFalse( get_transient( Update_Repository::TRANSIENT_KEY ) );
	}

	public function test_caches_not_invalidated_when_same_key_stored(): void {
		$this->license_repository->store_key( 'LWSW-UNIFIED-PRO-2026' );

		// Populate all four caches.
		$this->license_manager->get_products( 'example.com' );
		$this->catalog_repository->get();
		$this->feature_repository->get( 'LWSW-UNIFIED-KAD-PRO-2026', 'example.com' );
		$this->update_repository->get( 'LWSW-UNIFIED-KAD-PRO-2026', 'example.com' );

		// Re-store the same key — caches should remain intact.
		$this->license_repository->store_key( 'LWSW-UNIFIED-PRO-2026' );

		$this->assertInstanceOf( Product_Collection::class, $this->license_repository->get_products() );
		$this->assertNotFalse( get_transient( Catalog_Repository::TRANSIENT_KEY ) );
		$this->assertNotFalse( get_transient( Feature_Repository::TRANSIENT_KEY ) );
		$this->assertNotFalse( get_transient( Update_Repository::TRANSIENT_KEY ) );
	}
}
