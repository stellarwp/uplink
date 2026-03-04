<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Licensing;

use StellarWP\Uplink\Licensing\Error_Code;
use StellarWP\Uplink\Licensing\Fixture_Client;
use StellarWP\Uplink\Licensing\License_Manager;
use StellarWP\Uplink\Licensing\Product_Repository;
use StellarWP\Uplink\Licensing\Registry\Product_Registry;
use StellarWP\Uplink\Licensing\Repositories\License_Repository;
use StellarWP\Uplink\Tests\UplinkTestCase;
use WP_Error;

/**
 * @since 3.0.0
 */
final class License_ManagerTest extends UplinkTestCase {

	private License_Manager $manager;

	protected function setUp(): void {
		parent::setUp();

		$product_repository = new Product_Repository(
			new Fixture_Client( codecept_data_dir( 'licensing' ) )
		);

		$this->manager = new License_Manager(
			new License_Repository(),
			new Product_Registry(),
			$product_repository
		);

		delete_option( License_Repository::OPTION_NAME );
	}

	protected function tearDown(): void {
		remove_all_filters( Product_Registry::FILTER );
		delete_option( License_Repository::OPTION_NAME );
		delete_transient( Product_Repository::TRANSIENT_KEY );
		parent::tearDown();
	}

	// -------------------------------------------------------------------------
	// get()
	// -------------------------------------------------------------------------

	public function test_get_returns_null_when_no_key_stored_and_no_registry(): void {
		$this->assertNull( $this->manager->get() );
	}

	public function test_get_returns_stored_key(): void {
		$this->manager->store( 'LWSW-UNIFIED-PRO-2026' );

		$this->assertSame( 'LWSW-UNIFIED-PRO-2026', $this->manager->get() );
	}

	public function test_get_falls_back_to_embedded_key_from_registry(): void {
		add_filter(
			Product_Registry::FILTER,
			static function ( array $products ) {
				$products[] = [
					'slug'         => 'give',
					'embedded_key' => 'LWSW-EMBEDDED-KEY',
					'name'         => 'GiveWP',
					'version'      => '3.0.0',
					'group'        => 'givewp',
				];
				return $products;
			} 
		);

		$this->assertSame( 'LWSW-EMBEDDED-KEY', $this->manager->get() );
	}

	public function test_get_auto_stores_embedded_key_on_discovery(): void {
		add_filter(
			Product_Registry::FILTER,
			static function ( array $products ) {
				$products[] = [
					'slug'         => 'give',
					'embedded_key' => 'LWSW-EMBEDDED-KEY',
				];
				return $products;
			} 
		);

		$this->manager->get();

		$this->assertSame( 'LWSW-EMBEDDED-KEY', get_option( License_Repository::OPTION_NAME ) );
	}

	public function test_stored_key_takes_precedence_over_registry(): void {
		$this->manager->store( 'LWSW-STORED-KEY' );

		add_filter(
			Product_Registry::FILTER,
			static function ( array $products ) {
				$products[] = [
					'slug'         => 'give',
					'embedded_key' => 'LWSW-EMBEDDED-KEY',
				];
				return $products;
			} 
		);

		$this->assertSame( 'LWSW-STORED-KEY', $this->manager->get() );
	}

	public function test_registry_filter_not_applied_when_stored_key_exists(): void {
		$this->manager->store( 'LWSW-STORED-KEY' );

		$filter_called = false;
		add_filter(
			Product_Registry::FILTER,
			static function ( array $products ) use ( &$filter_called ) {
				$filter_called = true;
				return $products;
			} 
		);

		$this->manager->get();

		$this->assertFalse( $filter_called );
	}

	public function test_get_returns_null_when_registry_has_no_embedded_keys(): void {
		add_filter(
			Product_Registry::FILTER,
			static function ( array $products ) {
				$products[] = [
					'slug' => 'give',
					'name' => 'GiveWP',
				];
				return $products;
			} 
		);

		$this->assertNull( $this->manager->get() );
	}

	// -------------------------------------------------------------------------
	// store() / delete()
	// -------------------------------------------------------------------------

	public function test_store_persists_key(): void {
		$this->manager->store( 'LWSW-UNIFIED-PRO-2026' );

		$this->assertSame( 'LWSW-UNIFIED-PRO-2026', get_option( License_Repository::OPTION_NAME ) );
	}

	public function test_store_returns_true_on_success(): void {
		$this->assertTrue( $this->manager->store( 'LWSW-UNIFIED-PRO-2026' ) );
	}

	public function test_store_returns_false_for_key_without_lwsw_prefix(): void {
		$this->assertFalse( $this->manager->store( 'INVALID-KEY' ) );
	}

	public function test_store_does_not_persist_invalid_key(): void {
		$this->manager->store( 'INVALID-KEY' );

		$this->assertEmpty( get_option( License_Repository::OPTION_NAME ) );
	}

	public function test_delete_removes_stored_key(): void {
		$this->manager->store( 'LWSW-UNIFIED-PRO-2026' );
		$this->manager->delete();

		$this->assertNull( $this->manager->get() );
	}

	// -------------------------------------------------------------------------
	// exists()
	// -------------------------------------------------------------------------

	public function test_exists_returns_false_when_no_key(): void {
		$this->assertFalse( $this->manager->exists() );
	}

	public function test_exists_returns_true_when_key_stored(): void {
		$this->manager->store( 'LWSW-UNIFIED-PRO-2026' );

		$this->assertTrue( $this->manager->exists() );
	}

	public function test_exists_returns_true_when_registry_provides_embedded_key(): void {
		add_filter(
			Product_Registry::FILTER,
			static function ( array $products ) {
				$products[] = [
					'slug'         => 'give',
					'embedded_key' => 'LWSW-EMBEDDED-KEY',
				];
				return $products;
			}
		);

		$this->assertTrue( $this->manager->exists() );
	}

	// -------------------------------------------------------------------------
	// validate_and_store()
	// -------------------------------------------------------------------------

	public function test_validate_and_store_returns_true_for_recognized_key(): void {
		$result = $this->manager->validate_and_store( 'LWSW-UNIFIED-PRO-2026', 'example.com' );

		$this->assertTrue( $result );
	}

	public function test_validate_and_store_persists_key_on_success(): void {
		$this->manager->validate_and_store( 'LWSW-UNIFIED-PRO-2026', 'example.com' );

		$this->assertSame( 'LWSW-UNIFIED-PRO-2026', get_option( License_Repository::OPTION_NAME ) );
	}

	public function test_validate_and_store_returns_error_for_unrecognized_key(): void {
		$result = $this->manager->validate_and_store( 'LWSW-DOES-NOT-EXIST', 'example.com' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( Error_Code::INVALID_KEY, $result->get_error_code() );
	}

	public function test_validate_and_store_does_not_persist_unrecognized_key(): void {
		$this->manager->validate_and_store( 'LWSW-DOES-NOT-EXIST', 'example.com' );

		$this->assertEmpty( get_option( License_Repository::OPTION_NAME ) );
	}

	public function test_validate_and_store_returns_error_for_invalid_format(): void {
		$result = $this->manager->validate_and_store( 'INVALID-KEY', 'example.com' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( Error_Code::INVALID_KEY, $result->get_error_code() );
	}
}
