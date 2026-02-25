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
			'slug'          => 'test-feature',
			'group'         => 'LearnDash',
			'tier'          => 'Tier 2',
			'name'          => 'Test Feature',
			'description'   => 'Test feature description.',
			'plugin_file'   => 'test-feature/test-feature.php',
			'is_available'  => true,
			'documentation' => 'https://example.com/docs',
		] );

		$this->assertInstanceOf( Zip::class, $feature );
		$this->assertSame( 'test-feature', $feature->get_slug() );
		$this->assertSame( 'LearnDash', $feature->get_group() );
		$this->assertSame( 'Tier 2', $feature->get_tier() );
		$this->assertSame( 'Test Feature', $feature->get_name() );
		$this->assertSame( 'Test feature description.', $feature->get_description() );
		$this->assertSame( 'zip', $feature->get_type() );
		$this->assertSame( 'test-feature/test-feature.php', $feature->get_plugin_file() );
		$this->assertTrue( $feature->is_available() );
		$this->assertSame( 'https://example.com/docs', $feature->get_documentation() );
	}

	/**
	 * Tests that the description defaults to an empty string when omitted from the array.
	 *
	 * @return void
	 */
	public function test_it_defaults_description_to_empty_string(): void {
		$feature = Zip::from_array( [
			'slug'         => 'test-feature',
			'group'        => 'LearnDash',
			'tier'         => 'Tier 2',
			'name'         => 'Test Feature',
			'plugin_file'  => 'test-feature/test-feature.php',
			'is_available' => false,
		] );

		$this->assertSame( '', $feature->get_description() );
	}

	/**
	 * Tests that the type is always "zip" regardless of constructor arguments.
	 *
	 * @return void
	 */
	public function test_it_always_has_zip_type(): void {
		$feature = new Zip( 'test-feature', 'LearnDash', 'Tier 2', 'Test Feature', 'Test feature description.', 'test-feature/test-feature.php', true );

		$this->assertSame( 'zip', $feature->get_type() );
	}
}
