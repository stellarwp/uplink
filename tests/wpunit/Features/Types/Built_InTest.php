<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Features\Types;

use StellarWP\Uplink\Features\Types\Built_In;
use StellarWP\Uplink\Tests\UplinkTestCase;

final class Built_InTest extends UplinkTestCase {

	/**
	 * Tests a Built_In feature can be hydrated from an associative array.
	 *
	 * @return void
	 */
	public function test_it_creates_from_array(): void {
		$feature = Built_In::from_array( [
			'slug'          => 'test-feature',
			'group'         => 'TEC',
			'tier'          => 'Tier 1',
			'name'          => 'Test Feature',
			'description'   => 'Test feature description.',
			'is_available'  => true,
			'documentation' => 'https://example.com/docs',
		] );

		$this->assertInstanceOf( Built_In::class, $feature );
		$this->assertSame( 'test-feature', $feature->get_slug() );
		$this->assertSame( 'TEC', $feature->get_group() );
		$this->assertSame( 'Tier 1', $feature->get_tier() );
		$this->assertSame( 'Test Feature', $feature->get_name() );
		$this->assertSame( 'Test feature description.', $feature->get_description() );
		$this->assertSame( 'built_in', $feature->get_type() );
		$this->assertTrue( $feature->is_available() );
		$this->assertSame( 'https://example.com/docs', $feature->get_documentation() );
	}

	/**
	 * Tests that the description defaults to an empty string when omitted from the array.
	 *
	 * @return void
	 */
	public function test_it_defaults_description_to_empty_string(): void {
		$feature = Built_In::from_array( [
			'slug'         => 'test-feature',
			'group'        => 'TEC',
			'tier'         => 'Tier 1',
			'name'         => 'Test Feature',
			'is_available' => true,
		] );

		$this->assertSame( '', $feature->get_description() );
	}

	/**
	 * Tests that the type is always "built_in" regardless of constructor arguments.
	 *
	 * @return void
	 */
	public function test_it_always_has_built_in_type(): void {
		$feature = new Built_In( 'test-feature', 'TEC', 'Tier 1', 'Test Feature', 'Test feature description.', true );

		$this->assertSame( 'built_in', $feature->get_type() );
	}
}
