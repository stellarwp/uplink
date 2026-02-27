<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Legacy;

use StellarWP\Uplink\Legacy\Legacy_License;
use StellarWP\Uplink\Tests\UplinkTestCase;

/**
 * @since 3.0.0
 */
final class Legacy_LicenseTest extends UplinkTestCase {

	/**
	 * @test
	 */
	public function it_sets_properties_via_constructor(): void {
		$license = new Legacy_License(
			'key-123',
			'my-plugin',
			'My Plugin',
			'StellarWP',
			'valid',
			'https://example.com/licenses'
		);

		$this->assertSame( 'key-123', $license->key );
		$this->assertSame( 'my-plugin', $license->slug );
		$this->assertSame( 'My Plugin', $license->name );
		$this->assertSame( 'StellarWP', $license->brand );
		$this->assertSame( 'valid', $license->status );
		$this->assertSame( 'https://example.com/licenses', $license->page_url );
	}

	/**
	 * @test
	 */
	public function it_uses_default_status_and_page_url_when_omitted(): void {
		$license = new Legacy_License( 'key-456', 'slug', 'Name', 'Brand' );

		$this->assertSame( 'unknown', $license->status );
		$this->assertSame( '', $license->page_url );
	}

	/**
	 * @test
	 */
	public function it_creates_instance_from_array_via_fromData(): void {
		$data = [
			'key'      => 'key-from-array',
			'slug'     => 'give-recurring',
			'name'     => 'Give Recurring',
			'brand'    => 'GiveWP',
			'status'   => 'expired',
			'page_url' => 'https://site.com/wp-admin/licenses',
		];

		$license = Legacy_License::fromData( $data );

		$this->assertInstanceOf( Legacy_License::class, $license );
		$this->assertSame( 'key-from-array', $license->key );
		$this->assertSame( 'give-recurring', $license->slug );
		$this->assertSame( 'Give Recurring', $license->name );
		$this->assertSame( 'GiveWP', $license->brand );
		$this->assertSame( 'expired', $license->status );
		$this->assertSame( 'https://site.com/wp-admin/licenses', $license->page_url );
	}

	/**
	 * @test
	 */
	public function it_uses_defaults_for_missing_array_keys(): void {
		$license = Legacy_License::fromData( [] );

		$this->assertSame( '', $license->key );
		$this->assertSame( '', $license->slug );
		$this->assertSame( '', $license->name );
		$this->assertSame( '', $license->brand );
		$this->assertSame( 'unknown', $license->status );
		$this->assertSame( '', $license->page_url );
	}

	/**
	 * @test
	 */
	public function it_casts_non_string_values_to_string_in_fromData(): void {
		$license = Legacy_License::fromData( [
			'key'   => 12345,
			'slug'  => 'num-slug',
			'name'  => 'Name',
			'brand' => 'Brand',
		] );

		$this->assertSame( '12345', $license->key );
		$this->assertSame( 'num-slug', $license->slug );
	}
}
