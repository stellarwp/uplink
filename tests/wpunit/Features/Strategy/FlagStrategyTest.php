<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Features\Strategy;

use StellarWP\Uplink\Features\Strategy\Flag_Strategy;
use StellarWP\Uplink\Features\Types\Flag;
use StellarWP\Uplink\Tests\UplinkTestCase;

/**
 * Tests for the Flag_Strategy feature-gating strategy.
 *
 * These tests exercise the strategy's logic against real WordPress state
 * (wp_options) via the WPLoader module. Flag features are toggled purely
 * via a DB flag — there is no plugin installation or activation involved.
 *
 * @see Flag_Strategy
 */
final class FlagStrategyTest extends UplinkTestCase {

	/**
	 * The option key for the test feature's stored state.
	 *
	 * Follows the convention: stellarwp_uplink_feature_{slug}_active
	 *
	 * @var string
	 */
	private const OPTION_KEY = 'stellarwp_uplink_feature_advanced-tickets_active';

	/**
	 * @var Flag_Strategy
	 */
	private $strategy;

	/**
	 * @var Flag
	 */
	private $feature;

	protected function setUp(): void {
		parent::setUp();

		$this->feature  = $this->make_flag_feature();
		$this->strategy = new Flag_Strategy( $this->feature );
	}

	protected function tearDown(): void {
		delete_option( self::OPTION_KEY );

		parent::tearDown();
	}

	// -------------------------------------------------------------------------
	// enable() tests
	// -------------------------------------------------------------------------

	/**
	 * enable() sets the DB flag to '1'.
	 */
	public function test_enable_sets_option_to_active(): void {
		$result = $this->strategy->enable();

		$this->assertTrue( $result );
		$this->assertSame( '1', get_option( self::OPTION_KEY ) );
	}

	/**
	 * enable() is idempotent: calling it twice still returns true and the
	 * option remains '1'.
	 */
	public function test_enable_is_idempotent(): void {
		$this->strategy->enable();
		$result = $this->strategy->enable();

		$this->assertTrue( $result );
		$this->assertSame( '1', get_option( self::OPTION_KEY ) );
	}

	/**
	 * enable() overwrites a previously disabled state.
	 */
	public function test_enable_overwrites_disabled_state(): void {
		update_option( self::OPTION_KEY, '0', true );

		$result = $this->strategy->enable();

		$this->assertTrue( $result );
		$this->assertSame( '1', get_option( self::OPTION_KEY ) );
	}

	// -------------------------------------------------------------------------
	// disable() tests
	// -------------------------------------------------------------------------

	/**
	 * disable() sets the DB flag to '0'.
	 */
	public function test_disable_sets_option_to_inactive(): void {
		update_option( self::OPTION_KEY, '1', true );

		$result = $this->strategy->disable();

		$this->assertTrue( $result );
		$this->assertSame( '0', get_option( self::OPTION_KEY ) );
	}

	/**
	 * disable() is idempotent: calling it twice still returns true and the
	 * option remains '0'.
	 */
	public function test_disable_is_idempotent(): void {
		$this->strategy->disable();
		$result = $this->strategy->disable();

		$this->assertTrue( $result );
		$this->assertSame( '0', get_option( self::OPTION_KEY ) );
	}

	/**
	 * disable() overwrites a previously enabled state.
	 */
	public function test_disable_overwrites_enabled_state(): void {
		update_option( self::OPTION_KEY, '1', true );

		$result = $this->strategy->disable();

		$this->assertTrue( $result );
		$this->assertSame( '0', get_option( self::OPTION_KEY ) );
	}

	// -------------------------------------------------------------------------
	// is_active() tests
	// -------------------------------------------------------------------------

	/**
	 * is_active() returns true when the DB flag is '1'.
	 */
	public function test_is_active_returns_true_when_enabled(): void {
		update_option( self::OPTION_KEY, '1', true );

		$this->assertTrue( $this->strategy->is_active() );
	}

	/**
	 * is_active() returns false when the DB flag is '0'.
	 */
	public function test_is_active_returns_false_when_disabled(): void {
		update_option( self::OPTION_KEY, '0', true );

		$this->assertFalse( $this->strategy->is_active() );
	}

	/**
	 * is_active() returns false when no option exists yet (default state).
	 */
	public function test_is_active_returns_false_when_no_option_exists(): void {
		$this->assertFalse( $this->strategy->is_active() );
	}

	// -------------------------------------------------------------------------
	// Independent state
	// -------------------------------------------------------------------------

	/**
	 * Different features have independent state — enabling one does not
	 * affect the other.
	 */
	public function test_features_have_independent_state(): void {
		$other         = $this->make_flag_feature( 'other-feature', 'Other', 'Another feature.' );
		$other_strategy = new Flag_Strategy( $other );

		$this->strategy->enable();

		$this->assertTrue( $this->strategy->is_active() );
		$this->assertFalse( $other_strategy->is_active() );

		// Clean up.
		delete_option( 'stellarwp_uplink_feature_other-feature_active' );
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Create a standard Flag feature for testing.
	 *
	 * @param string $slug        Feature slug.
	 * @param string $name        Display name.
	 * @param string $description Description.
	 *
	 * @return Flag
	 */
	private function make_flag_feature(
		string $slug = 'advanced-tickets',
		string $name = 'Advanced Tickets',
		string $description = 'Unlock advanced ticketing features.'
	): Flag {
		return new Flag(
			[
				'slug'         => $slug,
				'group'        => 'TEC',
				'tier'         => 'Tier 1',
				'name'         => $name,
				'description'  => $description,
				'is_available' => true,
			]
		);
	}
}
