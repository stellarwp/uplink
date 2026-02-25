<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Features\Types;

use StellarWP\Uplink\Features\Types\Feature;
use StellarWP\Uplink\Tests\UplinkTestCase;

final class FeatureTest extends UplinkTestCase {

	/**
	 * The feature instance under test.
	 *
	 * @var Feature
	 */
	private Feature $feature;

	/**
	 * Sets up a concrete Feature subclass before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->feature = new class( 'test-feature', 'Test Feature', 'A test feature.', 'test-type' ) extends Feature {

			/**
			 * Creates a Feature instance from an associative array.
			 *
			 * @param array<string, mixed> $data The feature data.
			 *
			 * @return static
			 */
			public static function from_array( array $data ) {
				return new self(
					$data['slug'],
					$data['name'],
					$data['description'] ?? '',
					$data['type'] ?? 'test-type'
				);
			}
		};
	}

	/**
	 * Tests that get_slug returns the slug passed to the constructor.
	 *
	 * @return void
	 */
	public function test_get_slug(): void {
		$this->assertSame( 'test-feature', $this->feature->get_slug() );
	}

	/**
	 * Tests that get_name returns the name passed to the constructor.
	 *
	 * @return void
	 */
	public function test_get_name(): void {
		$this->assertSame( 'Test Feature', $this->feature->get_name() );
	}

	/**
	 * Tests that get_description returns the description passed to the constructor.
	 *
	 * @return void
	 */
	public function test_get_description(): void {
		$this->assertSame( 'A test feature.', $this->feature->get_description() );
	}

	/**
	 * Tests that get_type returns the type passed to the constructor.
	 *
	 * @return void
	 */
	public function test_get_type(): void {
		$this->assertSame( 'test-type', $this->feature->get_type() );
	}

	/**
	 * Tests that from_array hydrates a Feature with the correct values.
	 *
	 * @return void
	 */
	public function test_from_array(): void {
		$feature = $this->feature::from_array( [
			'slug'        => 'from-array-feature',
			'name'        => 'From Array',
			'description' => 'Hydrated from array.',
			'type'        => 'custom-type',
		] );

		$this->assertInstanceOf( Feature::class, $feature );
		$this->assertSame( 'from-array-feature', $feature->get_slug() );
		$this->assertSame( 'From Array', $feature->get_name() );
		$this->assertSame( 'Hydrated from array.', $feature->get_description() );
		$this->assertSame( 'custom-type', $feature->get_type() );
	}
}
