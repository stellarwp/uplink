<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Features;

use StellarWP\Uplink\Catalog\Catalog_Repository;
use StellarWP\Uplink\Features\Feature_Repository;
use StellarWP\Uplink\Features\Manager;
use StellarWP\Uplink\Features\Strategy\Plugin_Strategy;
use StellarWP\Uplink\Features\Strategy\Theme_Strategy;
use StellarWP\Uplink\Features\Types\Plugin;
use StellarWP\Uplink\Licensing\Repositories\License_Repository;
use StellarWP\Uplink\Tests\UplinkTestCase;
use WP_Theme;

/**
 * Integration tests for the Provider's activation/deactivation sync hooks.
 *
 * These tests verify the full wiring path: WordPress hook → Provider delegate
 * → Plugin_Strategy → stored state update. They use fixture catalog/licensing
 * data so the Manager has real Plugin features in its collection.
 *
 * @see \StellarWP\Uplink\Features\Provider
 */
final class ProviderSyncHooksTest extends UplinkTestCase {

	/**
	 * The wp_identifier for the kad-blocks-pro fixture feature.
	 *
	 * @var string
	 */
	private const PLUGIN_FILE = 'kadence-blocks-pro/kadence-blocks-pro.php';

	/**
	 * The option key for kad-blocks-pro stored state.
	 *
	 * @var string
	 */
	private const OPTION_KEY = 'stellarwp_uplink_feature_kad-blocks-pro_active';

	protected function setUp(): void {
		parent::setUp();

		if ( ! defined( 'STELLARWP_UPLINK_FEATURES_USE_FIXTURE_DATA' ) ) {
			define( 'STELLARWP_UPLINK_FEATURES_USE_FIXTURE_DATA', true );
		}

		// Clear cached data so fixture resolution runs fresh.
		delete_transient( Feature_Repository::TRANSIENT_KEY );
		delete_transient( Catalog_Repository::TRANSIENT_KEY );
		delete_transient( License_Repository::PRODUCTS_TRANSIENT_KEY );

		// Set a license key that makes kad-blocks-pro available.
		update_option( License_Repository::KEY_OPTION_NAME, 'lwsw-unified-kad-pro-2026' );
	}

	protected function tearDown(): void {
		delete_option( self::OPTION_KEY );
		delete_option( License_Repository::KEY_OPTION_NAME );
		delete_transient( Feature_Repository::TRANSIENT_KEY );
		delete_transient( Catalog_Repository::TRANSIENT_KEY );
		delete_transient( License_Repository::PRODUCTS_TRANSIENT_KEY );

		parent::tearDown();
	}

	/**
	 * Verify the fixture feature is resolved as a Plugin with the expected wp_identifier.
	 *
	 * This is a sanity check — if this fails, the sync hook tests below are meaningless.
	 */
	public function test_fixture_feature_is_resolved_as_plugin(): void {
		$manager = $this->container->get( Manager::class );
		$feature = $manager->get_feature( 'kad-blocks-pro' );

		$this->assertInstanceOf( Plugin::class, $feature );
		$this->assertSame( self::PLUGIN_FILE, $feature->get_wp_identifier() );
	}

	/**
	 * Firing 'activated_plugin' updates stored state to true for a known feature.
	 */
	public function test_activated_plugin_hook_syncs_stored_state_to_true(): void {
		// Ensure no stored state exists initially.
		$this->assertFalse( get_option( self::OPTION_KEY, false ) );

		do_action( 'activated_plugin', self::PLUGIN_FILE, false );

		$this->assertSame( '1', get_option( self::OPTION_KEY ) );
	}

	/**
	 * Firing 'deactivated_plugin' updates stored state to false for a known feature.
	 */
	public function test_deactivated_plugin_hook_syncs_stored_state_to_false(): void {
		// Start with active stored state.
		update_option( self::OPTION_KEY, '1', true );

		do_action( 'deactivated_plugin', self::PLUGIN_FILE, false );

		$this->assertSame( '0', get_option( self::OPTION_KEY ) );
	}

	/**
	 * Firing 'activated_plugin' for an unknown plugin does not create any option.
	 */
	public function test_activated_plugin_hook_ignores_unknown_plugin(): void {
		do_action( 'activated_plugin', 'unknown-plugin/unknown-plugin.php', false );

		// No stored state should be written for unknown features.
		$this->assertFalse( get_option( self::OPTION_KEY, false ) );
	}

	/**
	 * Full round-trip: activate → verify stored true → deactivate → verify stored false.
	 */
	public function test_activation_deactivation_round_trip(): void {
		do_action( 'activated_plugin', self::PLUGIN_FILE, false );
		$this->assertSame( '1', get_option( self::OPTION_KEY ) );

		do_action( 'deactivated_plugin', self::PLUGIN_FILE, false );
		$this->assertSame( '0', get_option( self::OPTION_KEY ) );
	}

	/**
	 * The Plugin_Strategy retrieved from the container has a working feature_resolver.
	 */
	public function test_container_plugin_strategy_has_feature_resolver(): void {
		$strategy = $this->container->get( Plugin_Strategy::class );

		// Call on_plugin_activated directly — if the resolver is wired,
		// it should find the feature and update stored state.
		$strategy->on_plugin_activated( self::PLUGIN_FILE, false );

		$this->assertSame( '1', get_option( self::OPTION_KEY ) );
	}

	/**
	 * The Theme_Strategy retrieved from the container has a working feature_resolver.
	 *
	 * The kadence theme fixture has no wp_identifier set, so on_theme_switch
	 * should silently no-op — but this verifies the resolver callable is wired
	 * (no fatal errors from a missing resolver).
	 */
	public function test_container_theme_strategy_has_feature_resolver(): void {
		$strategy = $this->container->get( Theme_Strategy::class );

		$old_theme = new WP_Theme( 'some-old-theme', '' );
		$new_theme = new WP_Theme( 'kadence', '' );

		// Should not throw — the resolver is wired even though no theme
		// feature matches (kadence has no wp_identifier in the fixture).
		$strategy->on_theme_switch( 'Kadence', $new_theme, $old_theme );

		// No stored state should be written for a theme without a wp_identifier match.
		$this->assertFalse( get_option( 'stellarwp_uplink_feature_kadence_active', false ) );
	}

	/**
	 * Firing 'switch_theme' through WordPress hooks reaches the Theme_Strategy.
	 *
	 * The hook fires but no-ops because the fixture theme has no wp_identifier.
	 * This verifies the Provider wiring doesn't fatal.
	 */
	public function test_switch_theme_hook_reaches_theme_strategy(): void {
		$old_theme = new WP_Theme( 'some-old-theme', '' );
		$new_theme = new WP_Theme( 'kadence', '' );

		do_action( 'switch_theme', 'Kadence', $new_theme, $old_theme );

		// Silent no-op — no stored state written.
		$this->assertFalse( get_option( 'stellarwp_uplink_feature_kadence_active', false ) );
	}
}
