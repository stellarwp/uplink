<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Licensing\Registry;

use StellarWP\Uplink\Licensing\Registry\Registered_Product;
use StellarWP\Uplink\Tests\UplinkTestCase;

/**
 * @since 3.0.0
 */
final class Registered_ProductTest extends UplinkTestCase {

	public function test_from_array_returns_null_when_slug_missing(): void {
		$this->assertNull( Registered_Product::from_array( [ 'embedded_key' => 'KEY' ] ) );
	}

	public function test_from_array_returns_null_when_slug_empty(): void {
		$this->assertNull( Registered_Product::from_array( [ 'slug' => '' ] ) );
	}

	public function test_from_array_returns_null_when_slug_not_string(): void {
		$this->assertNull( Registered_Product::from_array( [ 'slug' => 123 ] ) );
	}

	public function test_from_array_creates_full_registration(): void {
		$product = Registered_Product::from_array(
			[
				'slug'         => 'give',
				'embedded_key' => 'LWSW-UNIFIED-PRO-2026',
				'name'         => 'GiveWP',
				'version'      => '3.0.0',
				'group'        => 'givewp',
			] 
		);

		$this->assertNotNull( $product );
		$this->assertSame( 'give', $product->slug );
		$this->assertSame( 'LWSW-UNIFIED-PRO-2026', $product->embedded_key );
		$this->assertSame( 'GiveWP', $product->name );
		$this->assertSame( '3.0.0', $product->version );
		$this->assertSame( 'givewp', $product->group );
	}

	public function test_from_array_allows_optional_fields_to_be_absent(): void {
		$product = Registered_Product::from_array( [ 'slug' => 'give' ] );

		$this->assertNotNull( $product );
		$this->assertSame( 'give', $product->slug );
		$this->assertNull( $product->embedded_key );
		$this->assertNull( $product->name );
		$this->assertNull( $product->version );
		$this->assertNull( $product->group );
	}

	public function test_from_array_treats_empty_embedded_key_as_null(): void {
		$product = Registered_Product::from_array(
			[
				'slug'         => 'give',
				'embedded_key' => '',
			] 
		);

		$this->assertNotNull( $product );
		$this->assertNull( $product->embedded_key );
	}

	public function test_from_array_treats_key_without_lwsw_prefix_as_null(): void {
		$product = Registered_Product::from_array(
			[
				'slug'         => 'give',
				'embedded_key' => 'INVALID-KEY-NO-PREFIX',
			] 
		);

		$this->assertNotNull( $product );
		$this->assertNull( $product->embedded_key );
	}

	public function test_from_array_accepts_key_with_lwsw_prefix(): void {
		$product = Registered_Product::from_array(
			[
				'slug'         => 'give',
				'embedded_key' => 'LWSW-SOME-UNIFIED-KEY',
			] 
		);

		$this->assertNotNull( $product );
		$this->assertSame( 'LWSW-SOME-UNIFIED-KEY', $product->embedded_key );
	}

	public function test_has_embedded_key_returns_true_when_key_present(): void {
		$product = Registered_Product::from_array(
			[
				'slug'         => 'give',
				'embedded_key' => 'LWSW-UNIFIED-PRO-2026',
			] 
		);

		$this->assertTrue( $product->has_embedded_key() );
	}

	public function test_has_embedded_key_returns_false_when_key_absent(): void {
		$product = Registered_Product::from_array( [ 'slug' => 'give' ] );

		$this->assertFalse( $product->has_embedded_key() );
	}
}
