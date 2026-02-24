<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Features;

use StellarWP\Uplink\Features\Built_In_Feature;
use StellarWP\Uplink\Tests\UplinkTestCase;

/**
 * Tests for the Built_In_Feature value object.
 *
 * Verifies that Built_In_Feature hard-codes the type to "built_in" and
 * correctly stores its core properties (slug, name, description) with no
 * additional properties beyond the base Feature class.
 *
 * @see Built_In_Feature
 */
final class BuiltInFeatureTest extends UplinkTestCase {

	/**
	 * Standard test values.
	 */
	private const SLUG        = 'advanced-tickets';
	private const NAME        = 'Advanced Tickets';
	private const DESCRIPTION = 'Unlock advanced ticketing features.';

	/**
	 * Create a Built_In_Feature with configurable values.
	 *
	 * @param string $slug        Feature slug.
	 * @param string $name        Display name.
	 * @param string $description Description.
	 *
	 * @return Built_In_Feature
	 */
	private function make_feature(
		string $slug = self::SLUG,
		string $name = self::NAME,
		string $description = self::DESCRIPTION
	): Built_In_Feature {
		return new Built_In_Feature( $slug, $name, $description );
	}

	// -------------------------------------------------------------------------
	// Inherited getters (from Feature)
	// -------------------------------------------------------------------------

	/**
	 * get_slug() returns the slug passed to the constructor.
	 */
	public function test_get_slug_returns_constructor_value(): void {
		$feature = $this->make_feature( 'my-feature' );

		$this->assertSame( 'my-feature', $feature->get_slug() );
	}

	/**
	 * get_name() returns the name passed to the constructor.
	 */
	public function test_get_name_returns_constructor_value(): void {
		$feature = $this->make_feature( self::SLUG, 'Custom Name' );

		$this->assertSame( 'Custom Name', $feature->get_name() );
	}

	/**
	 * get_description() returns the description passed to the constructor.
	 */
	public function test_get_description_returns_constructor_value(): void {
		$feature = $this->make_feature( self::SLUG, self::NAME, 'Custom description.' );

		$this->assertSame( 'Custom description.', $feature->get_description() );
	}

	// -------------------------------------------------------------------------
	// Hard-coded type
	// -------------------------------------------------------------------------

	/**
	 * get_type() always returns "built_in" regardless of constructor arguments.
	 * The type is hard-coded so the Manager can dispatch to Built_In_Strategy.
	 */
	public function test_get_type_always_returns_built_in(): void {
		$feature = $this->make_feature();

		$this->assertSame( 'built_in', $feature->get_type() );
	}

	// -------------------------------------------------------------------------
	// No additional properties
	// -------------------------------------------------------------------------

	/**
	 * Built_In_Feature has only three constructor parameters (no plugin_file,
	 * no download_url). Verifying the constructor signature is correct.
	 */
	public function test_constructor_accepts_exactly_three_parameters(): void {
		$feature = new Built_In_Feature( 'slug', 'Name', 'Description' );

		$this->assertSame( 'slug', $feature->get_slug() );
		$this->assertSame( 'Name', $feature->get_name() );
		$this->assertSame( 'Description', $feature->get_description() );
		$this->assertSame( 'built_in', $feature->get_type() );
	}

	// -------------------------------------------------------------------------
	// Full round-trip
	// -------------------------------------------------------------------------

	/**
	 * All getters return the correct values from a single constructor call.
	 */
	public function test_all_getters_return_correct_values(): void {
		$feature = new Built_In_Feature(
			'advanced-tickets',
			'Advanced Tickets',
			'Unlock advanced ticketing features.'
		);

		$this->assertSame( 'advanced-tickets', $feature->get_slug() );
		$this->assertSame( 'Advanced Tickets', $feature->get_name() );
		$this->assertSame( 'Unlock advanced ticketing features.', $feature->get_description() );
		$this->assertSame( 'built_in', $feature->get_type() );
	}

}
