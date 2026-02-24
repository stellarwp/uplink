<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Features\Types;

use StellarWP\Uplink\Features\Types\Zip;
use StellarWP\Uplink\Tests\UplinkTestCase;

final class ZipTest extends UplinkTestCase {

	/**
	 * Tests a Zip feature can be hydrated from an associative array.
	 *
	 * @return void
	 */
	public function test_it_creates_from_array(): void {
		$feature = Zip::from_array( [
			'slug'        => 'test-feature',
			'name'        => 'Test Feature',
			'description' => 'Test feature description.',
			'plugin_file' => 'test-feature/test-feature.php',
		] );

		$this->assertInstanceOf( Zip::class, $feature );
		$this->assertSame( 'test-feature', $feature->get_slug() );
		$this->assertSame( 'Test Feature', $feature->get_name() );
		$this->assertSame( 'Test feature description.', $feature->get_description() );
		$this->assertSame( 'zip', $feature->get_type() );
		$this->assertSame( 'test-feature/test-feature.php', $feature->get_plugin_file() );
	}

	/**
	 * Tests that the description defaults to an empty string when omitted from the array.
	 *
	 * @return void
	 */
	public function test_it_defaults_description_to_empty_string(): void {
		$feature = Zip::from_array( [
			'slug'        => 'test-feature',
			'name'        => 'Test Feature',
			'plugin_file' => 'test-feature/test-feature.php',
		] );

		$this->assertSame( '', $feature->get_description() );
	}

	/**
	 * Tests that the type is always "zip" regardless of constructor arguments.
	 *
	 * @return void
	 */
	public function test_it_always_has_zip_type(): void {
		$feature = new Zip( 'test-feature', 'Test Feature', 'Test feature description.', 'test-feature/test-feature.php' );

		$this->assertSame( 'zip', $feature->get_type() );
	}
}
