<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Features\Update;

use StellarWP\Uplink\Features\Update\Handler;
use StellarWP\Uplink\Features\Update\Provider;
use StellarWP\Uplink\Features\Update\Resolve_Update_Data;
use StellarWP\Uplink\Features\Update\Update_Repository;
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
	 * Tests that Update_Repository is registered as a singleton in the container.
	 *
	 * @return void
	 */
	public function test_it_registers_update_repository_singleton(): void {
		$this->assertInstanceOf( Update_Repository::class, $this->container->get( Update_Repository::class ) );
	}

	/**
	 * Tests that Handler is registered as a singleton in the container.
	 *
	 * @return void
	 */
	public function test_it_registers_handler_singleton(): void {
		$this->assertInstanceOf( Handler::class, $this->container->get( Handler::class ) );
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
	 * @return void
	 */
	public function test_it_registers_hooks_when_version_leader(): void {
		$provider = $this->container->get( Provider::class );
		$provider->register_hooks();

		$this->assertSame(
			15,
			has_filter( 'plugins_api', [ $this->container->get( Handler::class ), 'filter_plugins_api' ] ),
			'plugins_api should have a callback at priority 15.'
		);

		$this->assertSame(
			15,
			has_filter( 'pre_set_site_transient_update_plugins', [ $this->container->get( Handler::class ), 'filter_update_check' ] ),
			'pre_set_site_transient_update_plugins should have a callback at priority 15.'
		);
	}

	/**
	 * Tests that hooks are not registered when a higher Uplink version exists.
	 *
	 * @return void
	 */
	public function test_it_does_not_register_hooks_when_higher_version_exists(): void {
		add_filter(
			'stellarwp/uplink/highest_version',
			static function () {
				return '99.0.0';
			}
		);

		$provider = new Provider( $this->container );
		$provider->register();
		$provider->register_hooks();

		$this->assertFalse(
			has_filter( 'plugins_api', [ $this->container->get( Handler::class ), 'filter_plugins_api' ] ),
			'plugins_api should not have a callback when a higher version exists.'
		);
	}
}
