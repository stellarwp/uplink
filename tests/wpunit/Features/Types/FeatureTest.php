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

		$this->feature = new class( [
			'slug'              => 'test-feature',
			'group'             => 'TEC',
			'tier'              => 'Tier 1',
			'name'              => 'Test Feature',
			'description'       => 'A test feature.',
			'type'              => 'test-type',
			'is_available'      => true,
			'documentation_url' => 'https://example.com/docs',
		] ) extends Feature {

			/**
			 * Creates a Feature instance from an associative array.
			 *
			 * @param array<string, mixed> $data The feature data.
			 *
			 * @return static
			 */
			public static function from_array( array $data ) {
				return new self(
					[
						'slug'              => $data['slug'],
						'group'             => $data['group'],
						'tier'              => $data['tier'],
						'name'              => $data['name'],
						'description'       => $data['description'] ?? '',
						'type'              => $data['type'] ?? 'test-type',
						'is_available'      => $data['is_available'],
						'documentation_url' => $data['documentation_url'] ?? '',
					] 
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
	 * Tests that get_group returns the group passed to the constructor.
	 *
	 * @return void
	 */
	public function test_get_group(): void {
		$this->assertSame( 'TEC', $this->feature->get_group() );
	}

	/**
	 * Tests that get_tier returns the tier passed to the constructor.
	 *
	 * @return void
	 */
	public function test_get_tier(): void {
		$this->assertSame( 'Tier 1', $this->feature->get_tier() );
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
	 * Tests that is_available returns the value passed to the constructor.
	 *
	 * @return void
	 */
	public function test_is_available(): void {
		$this->assertTrue( $this->feature->is_available() );
	}

	/**
	 * Tests that get_documentation_url returns the URL passed to the constructor.
	 *
	 * @return void
	 */
	public function test_get_documentation_url(): void {
		$this->assertSame( 'https://example.com/docs', $this->feature->get_documentation_url() );
	}

	/**
	 * Tests that to_array returns the expected associative array.
	 *
	 * @return void
	 */
	public function test_to_array(): void {
		$result = $this->feature->to_array();

		$this->assertSame(
			[
				'slug'              => 'test-feature',
				'group'             => 'TEC',
				'tier'              => 'Tier 1',
				'name'              => 'Test Feature',
				'description'       => 'A test feature.',
				'type'              => 'test-type',
				'is_available'      => true,
				'documentation_url' => 'https://example.com/docs',
			],
			$result 
		);
	}

	/**
	 * Tests that from_array hydrates a Feature with the correct values.
	 *
	 * @return void
	 */
	public function test_from_array(): void {
		$feature = $this->feature::from_array(
			[
				'slug'              => 'from-array-feature',
				'group'             => 'LearnDash',
				'tier'              => 'Tier 2',
				'name'              => 'From Array',
				'description'       => 'Hydrated from array.',
				'type'              => 'custom-type',
				'is_available'      => false,
				'documentation_url' => 'https://example.com/learn-more',
			] 
		);

		$this->assertInstanceOf( Feature::class, $feature );
		$this->assertSame( 'from-array-feature', $feature->get_slug() );
		$this->assertSame( 'LearnDash', $feature->get_group() );
		$this->assertSame( 'Tier 2', $feature->get_tier() );
		$this->assertSame( 'From Array', $feature->get_name() );
		$this->assertSame( 'Hydrated from array.', $feature->get_description() );
		$this->assertSame( 'custom-type', $feature->get_type() );
		$this->assertFalse( $feature->is_available() );
		$this->assertSame( 'https://example.com/learn-more', $feature->get_documentation_url() );
	}
}
