<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Features;

use StellarWP\Uplink\Features\Feature;
use StellarWP\Uplink\Tests\UplinkTestCase;

/**
 * Tests for the abstract Feature value object.
 *
 * Since Feature is abstract, we use a minimal anonymous subclass to exercise
 * the base constructor and getter behavior shared by all Feature subclasses.
 *
 * @see Feature
 */
final class FeatureTest extends UplinkTestCase {

	/**
	 * Create a concrete Feature instance for testing.
	 *
	 * @param string $slug        Feature slug.
	 * @param string $name        Display name.
	 * @param string $description Description.
	 * @param string $type        Strategy type.
	 *
	 * @return Feature
	 */
	private function make_feature(
		string $slug = 'test-feature',
		string $name = 'Test Feature',
		string $description = 'A test feature.',
		string $type = 'test'
	): Feature {
		return new class ( $slug, $name, $description, $type ) extends Feature {
		};
	}

	/**
	 * Constructor stores slug and get_slug() returns it.
	 */
	public function test_get_slug_returns_constructor_value(): void {
		$feature = $this->make_feature( 'my-slug' );

		$this->assertSame( 'my-slug', $feature->get_slug() );
	}

	/**
	 * Constructor stores name and get_name() returns it.
	 */
	public function test_get_name_returns_constructor_value(): void {
		$feature = $this->make_feature( 'slug', 'My Feature Name' );

		$this->assertSame( 'My Feature Name', $feature->get_name() );
	}

	/**
	 * Constructor stores description and get_description() returns it.
	 */
	public function test_get_description_returns_constructor_value(): void {
		$feature = $this->make_feature( 'slug', 'Name', 'A detailed description.' );

		$this->assertSame( 'A detailed description.', $feature->get_description() );
	}

	/**
	 * Constructor stores type and get_type() returns it.
	 */
	public function test_get_type_returns_constructor_value(): void {
		$feature = $this->make_feature( 'slug', 'Name', 'Desc', 'custom-type' );

		$this->assertSame( 'custom-type', $feature->get_type() );
	}

	/**
	 * All four getters return the exact values passed to the constructor.
	 */
	public function test_all_getters_return_correct_values(): void {
		$feature = $this->make_feature( 'export', 'Stellar Export', 'Exports data.', 'zip' );

		$this->assertSame( 'export', $feature->get_slug() );
		$this->assertSame( 'Stellar Export', $feature->get_name() );
		$this->assertSame( 'Exports data.', $feature->get_description() );
		$this->assertSame( 'zip', $feature->get_type() );
	}

	/**
	 * Feature correctly stores empty strings without error.
	 */
	public function test_constructor_accepts_empty_strings(): void {
		$feature = $this->make_feature( '', '', '', '' );

		$this->assertSame( '', $feature->get_slug() );
		$this->assertSame( '', $feature->get_name() );
		$this->assertSame( '', $feature->get_description() );
		$this->assertSame( '', $feature->get_type() );
	}

	/**
	 * Feature correctly handles strings with special characters.
	 */
	public function test_constructor_preserves_special_characters(): void {
		$feature = $this->make_feature(
			'slug-with-dashes_and_underscores',
			'Feature with "Quotes" & <Symbols>',
			"Description with\nnewlines and\ttabs.",
			'type/with/slashes'
		);

		$this->assertSame( 'slug-with-dashes_and_underscores', $feature->get_slug() );
		$this->assertSame( 'Feature with "Quotes" & <Symbols>', $feature->get_name() );
		$this->assertSame( "Description with\nnewlines and\ttabs.", $feature->get_description() );
		$this->assertSame( 'type/with/slashes', $feature->get_type() );
	}

	/**
	 * Two Feature instances with the same arguments are independent objects.
	 */
	public function test_instances_are_independent(): void {
		$a = $this->make_feature( 'a', 'Feature A', 'First.', 'zip' );
		$b = $this->make_feature( 'b', 'Feature B', 'Second.', 'config' );

		$this->assertSame( 'a', $a->get_slug() );
		$this->assertSame( 'b', $b->get_slug() );
		$this->assertNotSame( $a->get_slug(), $b->get_slug() );
	}

}
