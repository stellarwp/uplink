<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Licensing\Registry;

use StellarWP\Uplink\Licensing\Registry\Product_Registry;
use StellarWP\Uplink\Licensing\Registry\Registered_Product;
use StellarWP\Uplink\Tests\UplinkTestCase;

/**
 * @since 3.0.0
 */
final class Product_RegistryTest extends UplinkTestCase {

	private Product_Registry $registry;

	protected function setUp(): void {
		parent::setUp();
		$this->registry = new Product_Registry();
	}

	protected function tearDown(): void {
		remove_all_filters( Product_Registry::FILTER );
		parent::tearDown();
	}

	public function test_all_returns_empty_array_with_no_filter(): void {
		$this->assertSame( [], $this->registry->all() );
	}

	public function test_all_normalizes_entries_to_registered_product_instances(): void {
		add_filter(
			Product_Registry::FILTER,
			static function ( array $products ) {
				$products[] = [
					'slug'         => 'give',
					'embedded_key' => 'LWSW-UNIFIED-PRO-2026',
					'name'         => 'GiveWP',
					'version'      => '3.0.0',
					'product'        => 'givewp',
				];
				return $products;
			} 
		);

		$result = $this->registry->all();

		$this->assertCount( 1, $result );
		$this->assertInstanceOf( Registered_Product::class, $result[0] );
		$this->assertSame( 'give', $result[0]->slug );
	}

	public function test_all_collects_from_multiple_callbacks(): void {
		add_filter(
			Product_Registry::FILTER,
			static function ( array $products ) {
				$products[] = [ 'slug' => 'give' ];
				return $products;
			} 
		);

		add_filter(
			Product_Registry::FILTER,
			static function ( array $products ) {
				$products[] = [ 'slug' => 'kadence' ];
				return $products;
			} 
		);

		$result = $this->registry->all();

		$this->assertCount( 2, $result );
		$this->assertSame( 'give', $result[0]->slug );
		$this->assertSame( 'kadence', $result[1]->slug );
	}

	public function test_all_skips_non_array_entries(): void {
		add_filter(
			Product_Registry::FILTER,
			static function ( array $products ) {
				$products[] = 'not-an-array';
				$products[] = [ 'slug' => 'give' ];
				return $products;
			} 
		);

		$result = $this->registry->all();

		$this->assertCount( 1, $result );
		$this->assertSame( 'give', $result[0]->slug );
	}

	public function test_all_skips_entries_without_slug(): void {
		add_filter(
			Product_Registry::FILTER,
			static function ( array $products ) {
				$products[] = [
					'embedded_key' => 'KEY',
					'name'         => 'No Slug',
				];
				$products[] = [ 'slug' => 'give' ];
				return $products;
			} 
		);

		$result = $this->registry->all();

		$this->assertCount( 1, $result );
		$this->assertSame( 'give', $result[0]->slug );
	}

	public function test_first_with_embedded_key_returns_null_when_no_products(): void {
		$this->assertNull( $this->registry->first_with_embedded_key() );
	}

	public function test_first_with_embedded_key_returns_null_when_no_product_has_key(): void {
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

		$this->assertNull( $this->registry->first_with_embedded_key() );
	}

	public function test_first_with_embedded_key_skips_products_without_key(): void {
		add_filter(
			Product_Registry::FILTER,
			static function ( array $products ) {
				$products[] = [ 'slug' => 'no-key' ];
				$products[] = [
					'slug'         => 'give',
					'embedded_key' => 'LWSW-GIVE-KEY',
				];
				return $products;
			} 
		);

		$result = $this->registry->first_with_embedded_key();

		$this->assertNotNull( $result );
		$this->assertSame( 'give', $result->slug );
		$this->assertSame( 'LWSW-GIVE-KEY', $result->embedded_key );
	}

	public function test_first_with_embedded_key_returns_first_match(): void {
		add_filter(
			Product_Registry::FILTER,
			static function ( array $products ) {
				$products[] = [
					'slug'         => 'give',
					'embedded_key' => 'LWSW-GIVE-KEY',
				];
				$products[] = [
					'slug'         => 'kadence',
					'embedded_key' => 'LWSW-KADENCE-KEY',
				];
				return $products;
			} 
		);

		$result = $this->registry->first_with_embedded_key();

		$this->assertNotNull( $result );
		$this->assertSame( 'give', $result->slug );
	}
}
