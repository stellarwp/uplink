<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Legacy;

use StellarWP\Uplink\Legacy\Legacy_License;
use StellarWP\Uplink\Tests\UplinkTestCase;

/**
 * @since 3.0.0
 */
final class Legacy_LicenseTest extends UplinkTestCase {

	/**
	 * @since 3.0.0
	 */
	public function it_sets_properties_via_from_data(): void {
		$license = Legacy_License::from_data(
			[
				'key'       => 'key-123',
				'slug'      => 'my-plugin',
				'name'      => 'My Plugin',
				'brand'     => 'StellarWP',
				'is_active' => true,
				'page_url'  => 'https://example.com/licenses',
			]
		);

		$this->assertSame( 'key-123', $license->key );
		$this->assertSame( 'my-plugin', $license->slug );
		$this->assertSame( 'My Plugin', $license->name );
		$this->assertSame( 'StellarWP', $license->brand );
		$this->assertTrue( $license->is_active );
		$this->assertSame( 'https://example.com/licenses', $license->page_url );
	}

	/**
	 * @since 3.0.0
	 */
	public function it_defaults_is_active_to_false_and_page_url_to_empty_when_omitted(): void {
		$license = Legacy_License::from_data(
			[
				'key'   => 'key-456',
				'slug'  => 'slug',
				'name'  => 'Name',
				'brand' => 'Brand',
			]
		);

		$this->assertFalse( $license->is_active );
		$this->assertSame( '', $license->page_url );
	}

	/**
	 * @since 3.0.0
	 */
	public function it_creates_inactive_instance_from_array_via_from_data(): void {
		$license = Legacy_License::from_data(
			[
				'key'       => 'key-from-array',
				'slug'      => 'give-recurring',
				'name'      => 'Give Recurring',
				'brand'     => 'GiveWP',
				'is_active' => false,
				'page_url'  => 'https://site.com/wp-admin/licenses',
			]
		);

		$this->assertInstanceOf( Legacy_License::class, $license );
		$this->assertSame( 'key-from-array', $license->key );
		$this->assertSame( 'give-recurring', $license->slug );
		$this->assertSame( 'Give Recurring', $license->name );
		$this->assertSame( 'GiveWP', $license->brand );
		$this->assertFalse( $license->is_active );
		$this->assertSame( 'https://site.com/wp-admin/licenses', $license->page_url );
	}

	/**
	 * @since 3.0.0
	 */
	public function it_uses_explicit_is_active_value(): void {
		$active = Legacy_License::from_data(
			[
				'key'       => 'k',
				'slug'      => 's',
				'name'      => 'N',
				'brand'     => 'B',
				'is_active' => true,
			] 
		);

		$inactive = Legacy_License::from_data(
			[
				'key'       => 'k',
				'slug'      => 's',
				'name'      => 'N',
				'brand'     => 'B',
				'is_active' => false,
			] 
		);

		$this->assertTrue( $active->is_active );
		$this->assertFalse( $inactive->is_active );
	}

	/**
	 * @since 3.0.0
	 */
	public function it_uses_defaults_for_missing_array_keys(): void {
		$license = Legacy_License::from_data( [] );

		$this->assertSame( '', $license->key );
		$this->assertSame( '', $license->slug );
		$this->assertSame( '', $license->name );
		$this->assertSame( '', $license->brand );
		$this->assertFalse( $license->is_active );
		$this->assertSame( '', $license->page_url );
	}

	/**
	 * @since 3.0.0
	 */
	public function it_casts_non_string_values_to_string_in_from_data(): void {
		$license = Legacy_License::from_data(
			[
				'key'   => 12345,
				'slug'  => 'num-slug',
				'name'  => 'Name',
				'brand' => 'Brand',
			]
		);

		$this->assertSame( '12345', $license->key );
		$this->assertSame( 'num-slug', $license->slug );
	}
}
