<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Features\Update;

use StellarWP\Uplink\Features\Update\Plugin_Handler;
use StellarWP\Uplink\Features\Update\Provider;
use StellarWP\Uplink\Features\Update\Resolve_Update_Data;
use StellarWP\Uplink\Features\Update\Theme_Handler;
use StellarWP\Uplink\Tests\UplinkTestCase;

final class ProviderTest extends UplinkTestCase {

	/**
	 * Tests that Resolve_Update_Data is registered as a singleton in the container.
	 *
	 * @return void
	 */
	public function test_it_registers_resolve_update_data_singleton(): void {
		$this->assertInstanceOf( Resolve_Update_Data::class, $this->container->get( Resolve_Update_Data::class ) );
	}

	/**
	 * Tests that Plugin_Handler is registered as a singleton in the container.
	 *
	 * @return void
	 */
	public function test_it_registers_plugin_handler_singleton(): void {
		$this->assertInstanceOf( Plugin_Handler::class, $this->container->get( Plugin_Handler::class ) );
	}

	/**
	 * Tests that Theme_Handler is registered as a singleton in the container.
	 *
	 * @return void
	 */
	public function test_it_registers_theme_handler_singleton(): void {
		$this->assertInstanceOf( Theme_Handler::class, $this->container->get( Theme_Handler::class ) );
	}

	/**
	 * Tests that init has a registered callback for hook registration.
	 *
	 * @return void
	 */
	public function test_it_defers_hook_registration_to_init(): void {
		$this->assertGreaterThan(
			0,
			has_action( 'init' ),
			'init should have a registered callback.'
		);
	}

	/**
	 * Tests that hooks are registered when this instance is the version leader.
	 *
	 * The bootstrap plugin claims leadership and runs register_hooks() via the
	 * init action before tests run. setUp() creates a new container, so the
	 * registered callback objects belong to the bootstrap container, not to
	 * $this->container. We verify the WP filter state directly rather than
	 * checking for a specific callback object.
	 *
	 * @return void
	 */
	public function test_it_registers_hooks_when_version_leader(): void {
		global $wp_filter;

		$this->assertArrayHasKey(
			15,
			$wp_filter['plugins_api']->callbacks ?? [],
			'plugins_api should have a callback at priority 15.'
		);

		$this->assertArrayHasKey(
			15,
			$wp_filter['pre_set_site_transient_update_plugins']->callbacks ?? [],
			'pre_set_site_transient_update_plugins should have a callback at priority 15.'
		);

		$this->assertArrayHasKey(
			15,
			$wp_filter['themes_api']->callbacks ?? [],
			'themes_api should have a callback at priority 15.'
		);

		$this->assertArrayHasKey(
			15,
			$wp_filter['pre_set_site_transient_update_themes']->callbacks ?? [],
			'pre_set_site_transient_update_themes should have a callback at priority 15.'
		);
	}

	/**
	 * Tests that hooks are not registered when a higher Uplink version exists.
	 *
	 * @return void
	 */
	public function test_it_does_not_register_hooks_when_higher_version_exists(): void {
		// In production the higher-version instance claims the action first.
		// Simulate that here so this instance defers correctly.
		do_action( 'stellarwp/uplink/handled/feature_updates' ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

		$provider = new Provider( $this->container );
		$provider->register();
		$provider->register_hooks();

		$this->assertFalse(
			has_filter( 'plugins_api', [ $this->container->get( Plugin_Handler::class ), 'filter_plugins_api' ] ),
			'plugins_api should not have a callback when a higher version exists.'
		);

		$this->assertFalse(
			has_filter( 'themes_api', [ $this->container->get( Theme_Handler::class ), 'filter_themes_api' ] ),
			'themes_api should not have a callback when a higher version exists.'
		);
	}
}
