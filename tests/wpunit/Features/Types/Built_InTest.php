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
			'slug'        => 'test-feature',
			'name'        => 'Test Feature',
			'description' => 'Test feature description.',
		] );

		$this->assertInstanceOf( Built_In::class, $feature );
		$this->assertSame( 'test-feature', $feature->get_slug() );
		$this->assertSame( 'Test Feature', $feature->get_name() );
		$this->assertSame( 'Test feature description.', $feature->get_description() );
		$this->assertSame( 'built_in', $feature->get_type() );
	}

	/**
	 * Tests that the description defaults to an empty string when omitted from the array.
	 *
	 * @return void
	 */
	public function test_it_defaults_description_to_empty_string(): void {
		$feature = Built_In::from_array( [
			'slug' => 'test-feature',
			'name' => 'Test Feature',
		] );

		$this->assertSame( '', $feature->get_description() );
	}

	/**
	 * Tests that the type is always "built_in" regardless of constructor arguments.
	 *
	 * @return void
	 */
	public function test_it_always_has_built_in_type(): void {
		$feature = new Built_In( 'test-feature', 'Test Feature', 'Test feature description.' );

		$this->assertSame( 'built_in', $feature->get_type() );
	}
}
