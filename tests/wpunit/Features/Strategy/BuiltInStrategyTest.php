<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Features\Strategy;

use StellarWP\Uplink\Features\Strategy\Built_In_Strategy;
use StellarWP\Uplink\Features\Types\Built_In;
use StellarWP\Uplink\Features\Types\Feature;
use StellarWP\Uplink\Tests\UplinkTestCase;

/**
 * Tests for the Built_In_Strategy feature-gating strategy.
 *
 * These tests exercise the strategy's logic against real WordPress state
 * (wp_options) via the WPLoader module. Built-In features are toggled purely
 * via a DB flag — there is no plugin installation or activation involved.
 *
 * @see Built_In_Strategy
 */
final class BuiltInStrategyTest extends UplinkTestCase {

	/**
	 * The option key for the test feature's stored state.
	 *
	 * Follows the convention: stellarwp_uplink_feature_{slug}_active
	 *
	 * @var string
	 */
	private const OPTION_KEY = 'stellarwp_uplink_feature_advanced-tickets_active';

	/**
	 * @var Built_In_Strategy
	 */
	private $strategy;

	/**
	 * @var Built_In
	 */
	private $feature;

	protected function setUp(): void {
		parent::setUp();

		$this->strategy = new Built_In_Strategy();
		$this->feature  = $this->make_built_in_feature();
	}

	protected function tearDown(): void {
		delete_option( self::OPTION_KEY );

		parent::tearDown();
	}

	// -------------------------------------------------------------------------
	// enable() tests
	// -------------------------------------------------------------------------

	/**
	 * enable() must reject non-Built_In instances with a type mismatch error.
	 */
	public function test_enable_returns_type_mismatch_error_for_non_built_in_feature(): void {
		$non_built_in = $this->create_non_built_in_feature();

		$result = $this->strategy->enable( $non_built_in );

		$this->assertWPError( $result );
		$this->assertSame( 'feature_type_mismatch', $result->get_error_code() );
	}

	/**
	 * enable() sets the DB flag to '1'.
	 */
	public function test_enable_sets_option_to_active(): void {
		$result = $this->strategy->enable( $this->feature );

		$this->assertTrue( $result );
		$this->assertSame( '1', get_option( self::OPTION_KEY ) );
	}

	/**
	 * enable() is idempotent: calling it twice still returns true and the
	 * option remains '1'.
	 */
	public function test_enable_is_idempotent(): void {
		$this->strategy->enable( $this->feature );
		$result = $this->strategy->enable( $this->feature );

		$this->assertTrue( $result );
		$this->assertSame( '1', get_option( self::OPTION_KEY ) );
	}

	/**
	 * enable() overwrites a previously disabled state.
	 */
	public function test_enable_overwrites_disabled_state(): void {
		update_option( self::OPTION_KEY, '0', true );

		$result = $this->strategy->enable( $this->feature );

		$this->assertTrue( $result );
		$this->assertSame( '1', get_option( self::OPTION_KEY ) );
	}

	// -------------------------------------------------------------------------
	// disable() tests
	// -------------------------------------------------------------------------

	/**
	 * disable() must reject non-Built_In instances with a type mismatch error.
	 */
	public function test_disable_returns_type_mismatch_error_for_non_built_in_feature(): void {
		$non_built_in = $this->create_non_built_in_feature();

		$result = $this->strategy->disable( $non_built_in );

		$this->assertWPError( $result );
		$this->assertSame( 'feature_type_mismatch', $result->get_error_code() );
	}

	/**
	 * disable() sets the DB flag to '0'.
	 */
	public function test_disable_sets_option_to_inactive(): void {
		update_option( self::OPTION_KEY, '1', true );

		$result = $this->strategy->disable( $this->feature );

		$this->assertTrue( $result );
		$this->assertSame( '0', get_option( self::OPTION_KEY ) );
	}

	/**
	 * disable() is idempotent: calling it twice still returns true and the
	 * option remains '0'.
	 */
	public function test_disable_is_idempotent(): void {
		$this->strategy->disable( $this->feature );
		$result = $this->strategy->disable( $this->feature );

		$this->assertTrue( $result );
		$this->assertSame( '0', get_option( self::OPTION_KEY ) );
	}

	/**
	 * disable() overwrites a previously enabled state.
	 */
	public function test_disable_overwrites_enabled_state(): void {
		update_option( self::OPTION_KEY, '1', true );

		$result = $this->strategy->disable( $this->feature );

		$this->assertTrue( $result );
		$this->assertSame( '0', get_option( self::OPTION_KEY ) );
	}

	// -------------------------------------------------------------------------
	// is_active() tests
	// -------------------------------------------------------------------------

	/**
	 * is_active() returns false for non-Built_In instances.
	 */
	public function test_is_active_returns_false_for_non_built_in_feature(): void {
		$non_built_in = $this->create_non_built_in_feature();

		$this->assertFalse( $this->strategy->is_active( $non_built_in ) );
	}

	/**
	 * is_active() returns true when the DB flag is '1'.
	 */
	public function test_is_active_returns_true_when_enabled(): void {
		update_option( self::OPTION_KEY, '1', true );

		$this->assertTrue( $this->strategy->is_active( $this->feature ) );
	}

	/**
	 * is_active() returns false when the DB flag is '0'.
	 */
	public function test_is_active_returns_false_when_disabled(): void {
		update_option( self::OPTION_KEY, '0', true );

		$this->assertFalse( $this->strategy->is_active( $this->feature ) );
	}

	/**
	 * is_active() returns false when no option exists yet (default state).
	 */
	public function test_is_active_returns_false_when_no_option_exists(): void {
		$this->assertFalse( $this->strategy->is_active( $this->feature ) );
	}

	// -------------------------------------------------------------------------
	// enable/disable/is_active round-trip
	// -------------------------------------------------------------------------

	/**
	 * Full lifecycle: enable → is_active → disable → is_active.
	 */
	public function test_full_enable_disable_lifecycle(): void {
		// Initially inactive.
		$this->assertFalse( $this->strategy->is_active( $this->feature ) );

		// Enable.
		$this->assertTrue( $this->strategy->enable( $this->feature ) );
		$this->assertTrue( $this->strategy->is_active( $this->feature ) );

		// Disable.
		$this->assertTrue( $this->strategy->disable( $this->feature ) );
		$this->assertFalse( $this->strategy->is_active( $this->feature ) );
	}

	/**
	 * Different features have independent state — enabling one does not
	 * affect the other.
	 */
	public function test_features_have_independent_state(): void {
		$other = new Built_In( 'other-feature', 'TEC', 'Tier 1', 'Other', 'Another feature.', true );

		$this->strategy->enable( $this->feature );

		$this->assertTrue( $this->strategy->is_active( $this->feature ) );
		$this->assertFalse( $this->strategy->is_active( $other ) );

		// Clean up.
		delete_option( 'stellarwp_uplink_feature_other-feature_active' );
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Create a standard Built_In feature for testing.
	 *
	 * @param string $slug        Feature slug.
	 * @param string $name        Display name.
	 * @param string $description Description.
	 *
	 * @return Built_In
	 */
	private function make_built_in_feature(
		string $slug = 'advanced-tickets',
		string $name = 'Advanced Tickets',
		string $description = 'Unlock advanced ticketing features.'
	): Built_In {
		return new Built_In( $slug, 'TEC', 'Tier 1', $name, $description, true );
	}

	/**
	 * Create a non-Built_In Feature subclass for type-guard testing.
	 *
	 * Uses an anonymous class to avoid creating a whole new file for a test-
	 * only concrete subclass.
	 *
	 * @return Feature
	 */
	private function create_non_built_in_feature(): Feature {
		return new class ( 'not-built-in', 'Test', 'Tier 1', 'Not Built-In', 'Not a built-in feature.', 'other', true ) extends Feature {

			/**
			 * @inheritDoc
			 */
			public static function from_array( array $data ) {
				return new self( $data['slug'], $data['group'] ?? '', $data['tier'] ?? '', $data['name'], $data['description'] ?? '', $data['type'] ?? 'other', $data['is_available'] ?? true );
			}
		};
	}

}
