<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests;

use lucatume\WPBrowser\TestCase\WPTestCase;
use StellarWP\ContainerContract\ContainerInterface;
use StellarWP\Uplink\Config;
use StellarWP\Uplink\Uplink;
use WP_Screen;

/**
 * @mixin \Codeception\Test\Unit
 * @mixin \PHPUnit\Framework\TestCase
 * @mixin \Codeception\PHPUnit\TestCase
 */
class UplinkTestCase extends WPTestCase {

	/**
	 * @var ContainerInterface|\lucatume\DI52\Container
	 */
	protected $container;

	protected function setUp(): void {
		parent::setUp();

		$container = new Container();
		Config::set_container( $container );
		Config::set_hook_prefix( 'test' );

		Uplink::init();

		$this->container = Config::get_container();
	}

	protected function tearDown(): void {
		Config::reset();

		// Reset any current screen implementations.
		$GLOBALS['current_screen'] = null;

		parent::tearDown();
	}

	/**
	 * Mock we're inside the wp-admin dashboard and fire off the admin_init hook.
	 *
	 * @param  bool  $network  Whether we're in the network dashboard.
	 *
	 * @return void
	 */
	protected function admin_init( bool $network = false ): void {
		$screen                    = WP_Screen::get( $network ? 'dashboard-network' : 'dashboard' );
		$GLOBALS['current_screen'] = $screen;

		if ( $network ) {
			$this->assertTrue( $screen->in_admin( 'network' ) );
		}

		$this->assertTrue( $screen->in_admin() );

		// Fire off admin_init to run any of our events hooked into this action.
		do_action( 'admin_init' );
	}

}
