<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Resources;

use ReflectionProperty;
use StellarWP\Uplink\Register;
use StellarWP\Uplink\Resources\License;
use StellarWP\Uplink\Resources\Plugin;
use StellarWP\Uplink\Tests\UplinkTestCase;
use StellarWP\Uplink\Uplink;

final class LicenseTest extends UplinkTestCase {

	/**
	 * The resource to test license key origin behavior against.
	 *
	 * @var Plugin
	 */
	private $resource;

	/**
	 * Sets up the test fixture.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->resource = Register::plugin(
			'sample',
			'Lib Sample',
			'1.0.10',
			'uplink/index.php',
			Uplink::class
		);
	}

	/**
	 * Tests that key_origin is set to 'filter' when the global license_get_key filter changes the key.
	 *
	 * @return void
	 */
	public function test_sets_key_origin_to_filtered_when_key_is_changed_by_filter(): void {
		$filtered_key = 'filtered-license-key-12345';

		add_filter( 'stellarwp/uplink/test/license_get_key', function () use ( $filtered_key ) {
			return $filtered_key;
		} );

		$license = $this->resource->get_license_object();
		$key     = $license->get_key();

		$this->assertSame( $filtered_key, $key );

		$key_origin = $this->get_key_origin( $license );

		$this->assertSame( 'filter', $key_origin );
	}

	/**
	 * Tests that key_origin is set to 'filter' when the slug-specific license_get_key filter changes the key.
	 *
	 * @return void
	 */
	public function test_sets_key_origin_to_filtered_when_key_is_changed_by_slug_specific_filter(): void {
		$filtered_key = 'slug-filtered-license-key-12345';

		add_filter( 'stellarwp/uplink/test/sample/license_get_key', function () use ( $filtered_key ) {
			return $filtered_key;
		} );

		$license = $this->resource->get_license_object();
		$key     = $license->get_key();

		$this->assertSame( $filtered_key, $key );

		$key_origin = $this->get_key_origin( $license );

		$this->assertSame( 'filter', $key_origin );
	}

	/**
	 * Tests that key_origin is not set to 'filter' when the filter returns the key unchanged.
	 *
	 * @return void
	 */
	public function test_does_not_set_key_origin_to_filtered_when_filter_returns_same_key(): void {
		$original_key = 'original-license-key-12345';

		$this->resource->set_license_key( $original_key );

		add_filter( 'stellarwp/uplink/test/license_get_key', function ( $key ) {
			return $key;
		} );

		$license = $this->resource->get_license_object();
		$key     = $license->get_key();

		$this->assertSame( $original_key, $key );

		$key_origin = $this->get_key_origin( $license );

		$this->assertNotSame( 'filter', $key_origin );
	}

	/**
	 * Tests that key_origin is not set to 'filter' when the filter returns an empty string.
	 *
	 * @return void
	 */
	public function test_does_not_set_key_origin_to_filtered_when_filter_returns_empty(): void {
		add_filter( 'stellarwp/uplink/test/license_get_key', function () {
			return '';
		} );

		$license = $this->resource->get_license_object();
		$key     = $license->get_key();

		$this->assertSame( '', $key );

		$key_origin = $this->get_key_origin( $license );

		$this->assertNotSame( 'filter', $key_origin );
	}

	/**
	 * Gets the protected key_origin property via reflection.
	 *
	 * @param License $license The license instance.
	 *
	 * @return string|null
	 */
	private function get_key_origin( License $license ) {
		$property = new ReflectionProperty( License::class, 'key_origin' );
		$property->setAccessible( true );

		return $property->getValue( $license );
	}
}
