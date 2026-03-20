<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Licensing\Repositories;

use StellarWP\Uplink\Catalog\Catalog_Repository;
use StellarWP\Uplink\Catalog\Clients\Fixture_Client as Catalog_Fixture;
use StellarWP\Uplink\Licensing\Clients\Fixture_Client as Licensing_Fixture;
use StellarWP\Uplink\Licensing\License_Manager;
use StellarWP\Uplink\Licensing\Product_Collection;
use StellarWP\Uplink\Licensing\Registry\Product_Registry;
use StellarWP\Uplink\Licensing\Repositories\License_Repository;
use StellarWP\Uplink\Tests\UplinkTestCase;

/**
 * Integration tests verifying that changing the unified license key
 * invalidates upstream repository caches, causing fresh data
 * to be fetched on the next call.
 *
 * @since 3.0.0
 */
final class License_Key_Cache_InvalidationTest extends UplinkTestCase {

	private License_Repository $license_repository;
	private License_Manager $license_manager;
	private Catalog_Repository $catalog_repository;

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

		// Register cache invalidation hooks that providers normally wire up.
		add_action(
			'stellarwp/uplink/unified_license_key_changed',
			[ $this->license_repository, 'delete_products' ]
		);
		add_action(
			'stellarwp/uplink/unified_license_key_changed',
			static function () {
				delete_option( Catalog_Repository::CATALOG_STATE_OPTION_NAME );
			}
		);

		delete_option( License_Repository::PRODUCTS_STATE_OPTION_NAME );
		delete_option( Catalog_Repository::CATALOG_STATE_OPTION_NAME );
		delete_option( License_Repository::KEY_OPTION_NAME );
	}

	protected function tearDown(): void {
		remove_all_filters( 'stellarwp/uplink/unified_license_key_changed' );

		delete_option( License_Repository::PRODUCTS_STATE_OPTION_NAME );
		delete_option( Catalog_Repository::CATALOG_STATE_OPTION_NAME );
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
		// Seed a stale catalog state with a single fake product.
		$stale_state = [
			'collection'      => [
				[
					'product_slug' => 'stale-product',
					'tiers'        => [],
					'features'     => [],
				],
			],
			'last_success_at' => time() - 100,
			'last_failure_at' => null,
			'last_error'      => null,
		];
		update_option( Catalog_Repository::CATALOG_STATE_OPTION_NAME, $stale_state );

		// Confirm the stale cache is served.
		$cached = $this->catalog_repository->get();
		$this->assertCount( 1, $cached );
		$this->assertNotNull( $cached->get( 'stale-product' ) );

		// Change the license key — invalidates the catalog option.
		$this->license_repository->store_key( 'LWSW-UNIFIED-BASIC-2026' );

		// Fresh fetch should return the real catalog, not the stale single-product one.
		$fresh = $this->catalog_repository->get();
		$this->assertGreaterThan( 1, count( $fresh ) );
		$this->assertNull( $fresh->get( 'stale-product' ) );
		$this->assertNotNull( $fresh->get( 'kadence' ) );
	}

	public function test_all_caches_invalidated_on_key_change(): void {
		// Populate upstream caches.
		$this->license_repository->store_key( 'LWSW-UNIFIED-PRO-2026' );
		$this->license_manager->get_products( 'example.com' );
		$this->catalog_repository->get();

		$this->assertInstanceOf( Product_Collection::class, $this->license_repository->get_products() );
		$this->assertNotFalse( get_option( Catalog_Repository::CATALOG_STATE_OPTION_NAME ) );

		// Storing a new key should clear the upstream caches.
		$this->license_repository->store_key( 'LWSW-UNIFIED-BASIC-2026' );

		$this->assertNull( $this->license_repository->get_products() );
		$this->assertFalse( get_option( Catalog_Repository::CATALOG_STATE_OPTION_NAME ) );
	}

	public function test_all_caches_invalidated_on_key_delete(): void {
		$this->license_repository->store_key( 'LWSW-UNIFIED-PRO-2026' );

		// Populate upstream caches.
		$this->license_manager->get_products( 'example.com' );
		$this->catalog_repository->get();

		$this->assertInstanceOf( Product_Collection::class, $this->license_repository->get_products() );
		$this->assertNotFalse( get_option( Catalog_Repository::CATALOG_STATE_OPTION_NAME ) );

		// Deleting the key should clear the upstream caches.
		$this->license_repository->delete_key();

		$this->assertNull( $this->license_repository->get_products() );
		$this->assertFalse( get_option( Catalog_Repository::CATALOG_STATE_OPTION_NAME ) );
	}

	public function test_caches_not_invalidated_when_same_key_stored(): void {
		$this->license_repository->store_key( 'LWSW-UNIFIED-PRO-2026' );

		// Populate upstream caches.
		$this->license_manager->get_products( 'example.com' );
		$this->catalog_repository->get();

		// Re-store the same key — caches should remain intact.
		$this->license_repository->store_key( 'LWSW-UNIFIED-PRO-2026' );

		$this->assertInstanceOf( Product_Collection::class, $this->license_repository->get_products() );
		$this->assertNotFalse( get_option( Catalog_Repository::CATALOG_STATE_OPTION_NAME ) );
	}
}
