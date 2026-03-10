<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Features\Types;

use StellarWP\Uplink\Features\Types\Flag;
use StellarWP\Uplink\Tests\UplinkTestCase;

final class FlagTest extends UplinkTestCase {

	/**
	 * Tests a Flag feature can be hydrated from an associative array.
	 *
	 * @return void
	 */
	public function test_it_creates_from_array(): void {
		$feature = Flag::from_array(
			[
				'slug'              => 'test-feature',
				'group'             => 'TEC',
				'tier'              => 'Tier 1',
				'name'              => 'Test Feature',
				'description'       => 'Test feature description.',
				'is_available'      => true,
				'documentation_url' => 'https://example.com/docs',
			]
		);

		$this->assertInstanceOf( Flag::class, $feature );
		$this->assertSame( 'test-feature', $feature->get_slug() );
		$this->assertSame( 'TEC', $feature->get_group() );
		$this->assertSame( 'Tier 1', $feature->get_tier() );
		$this->assertSame( 'Test Feature', $feature->get_name() );
		$this->assertSame( 'Test feature description.', $feature->get_description() );
		$this->assertSame( 'flag', $feature->get_type() );
		$this->assertTrue( $feature->is_available() );
		$this->assertSame( 'https://example.com/docs', $feature->get_documentation_url() );
	}

	/**
	 * Tests that to_array returns the expected associative array.
	 *
	 * @return void
	 */
	public function test_to_array(): void {
		$feature = new Flag(
			[
				'slug'              => 'test-feature',
				'group'             => 'TEC',
				'tier'              => 'Tier 1',
				'name'              => 'Test Feature',
				'description'       => 'Test feature description.',
				'is_available'      => true,
				'documentation_url' => 'https://example.com/docs',
			]
		);

		$this->assertSame(
			[
				'slug'              => 'test-feature',
				'group'             => 'TEC',
				'tier'              => 'Tier 1',
				'name'              => 'Test Feature',
				'description'       => 'Test feature description.',
				'is_available'      => true,
				'documentation_url' => 'https://example.com/docs',
				'type'              => 'flag',
			],
			$feature->to_array()
		);
	}

	/**
	 * Tests that to_array round-trips through from_array.
	 *
	 * @return void
	 */
	public function test_to_array_round_trips_through_from_array(): void {
		$data = [
			'slug'              => 'test-feature',
			'group'             => 'TEC',
			'tier'              => 'Tier 1',
			'name'              => 'Test Feature',
			'description'       => 'Test feature description.',
			'type'              => 'flag',
			'is_available'      => true,
			'is_enabled'        => false,
			'documentation_url' => 'https://example.com/docs',
		];

		$feature = Flag::from_array( $data );

		$this->assertEquals( $data, $feature->to_array() );
	}

	/**
	 * Tests that the description defaults to an empty string when omitted from the array.
	 *
	 * @return void
	 */
	public function test_it_defaults_description_to_empty_string(): void {
		$feature = Flag::from_array(
			[
				'slug'         => 'test-feature',
				'group'        => 'TEC',
				'tier'         => 'Tier 1',
				'name'         => 'Test Feature',
				'is_available' => true,
			]
		);

		$this->assertSame( '', $feature->get_description() );
	}

	/**
	 * Tests that the type is always "flag" regardless of constructor arguments.
	 *
	 * @return void
	 */
	public function test_it_always_has_flag_type(): void {
		$feature = new Flag(
			[
				'slug'         => 'test-feature',
				'group'        => 'TEC',
				'tier'         => 'Tier 1',
				'name'         => 'Test Feature',
				'description'  => 'Test feature description.',
				'is_available' => true,
			]
		);

		$this->assertSame( 'flag', $feature->get_type() );
	}
}
