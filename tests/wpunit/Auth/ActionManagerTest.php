<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Auth;

use StellarWP\Uplink\Auth\Action_Manager;
use StellarWP\Uplink\Auth\Admin\Disconnect_Controller;
use StellarWP\Uplink\Config;
use StellarWP\Uplink\Register;
use StellarWP\Uplink\Resources\Collection;
use StellarWP\Uplink\Tests\Sample_Plugin;
use StellarWP\Uplink\Tests\UplinkTestCase;
use StellarWP\Uplink\Uplink;

final class ActionManagerTest extends UplinkTestCase {

	/**
	 * @var string
	 */
	private $slug_1 = 'sample-1';

	/**
	 * @var string
	 */
	private $slug_2 = 'sample-2';

	/**
	 * @var Action_Manager
	 */
	private $action_manager;

	protected function setUp(): void {
		parent::setUp();

		Config::set_token_auth_prefix( 'kadence_' );

		// Run init again to reload providers.
		Uplink::init();

		// Register 2 sample plugins as a developer would in their plugin.
		Register::plugin(
			$this->slug_1,
			'Lib Sample 1',
			'1.0.10',
			'uplink/index.php',
			Sample_Plugin::class
		);

		Register::plugin(
			$this->slug_2,
			'Lib Sample 2',
			'1.0.10',
			'uplink/index.php',
			Sample_Plugin::class
		);

		$this->action_manager = $this->container->get( Action_Manager::class );
	}

	protected function tearDown(): void {
		unset( $_REQUEST[ Disconnect_Controller::SLUG ] );

		parent::tearDown();
	}

	public function test_it_gets_the_correct_hook_name(): void {
		$plugin_1 = $this->container->get( Collection::class )->offsetGet( $this->slug_1 );
		$plugin_2 = $this->container->get( Collection::class )->offsetGet( $this->slug_2 );

		$this->assertSame( 'stellarwp/uplink/test/admin_action_sample-1', $this->action_manager->get_hook_name( $plugin_1 ) );
		$this->assertSame( 'stellarwp/uplink/test/admin_action_sample-2', $this->action_manager->get_hook_name( $plugin_2 ) );
	}

	public function test_it_registers_and_fires_actions(): void {
		$collection = $this->container->get( Collection::class );

		foreach ( $collection as $resource ) {
			$this->assertFalse( has_action( $this->action_manager->get_hook_name( $resource ) ) );
			$this->assertSame( 0, did_action( $this->action_manager->get_hook_name( $resource ) ) );
		}

		// Mock we're an admin inside the dashboard.
		$this->admin_init();

		foreach ( $collection as $resource ) {
			$this->assertTrue( has_action( $this->action_manager->get_hook_name( $resource ) ) );

			$_REQUEST[ Disconnect_Controller::SLUG ] = $resource->get_slug();

			// Fire off current_screen, which runs our actions, normally this would run once, but we want to test them all.
			do_action( 'current_screen', null );

			$this->assertSame( 1, did_action( $this->action_manager->get_hook_name( $resource ) ) );
		}
	}

}
