<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests;

use Codeception\TestCase\WPTestCase;
use RuntimeException;
use StellarWP\ContainerContract\ContainerInterface;
use StellarWP\Uplink\Config;
use StellarWP\Uplink\Uplink;

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
		// @phpstan-ignore-next-line
		parent::setUp();

		$container = new Container();
		Config::set_container( $container );
		Config::set_hook_prefix( 'test' );

		Uplink::init();

		$this->container = Config::get_container();
	}

	protected function tearDown(): void {
		Config::reset();

		parent::tearDown();
	}

	/**
	 * Fake that WordPress has a plugin activated by manually inserting the records.
	 *
	 * @param  string  $path          The path to the plugin file, e.g. my-plugin/my-plugin.php
	 * @param  bool    $network_wide  Whether this should happen network wide.
	 *
	 * @return void
	 */
	protected function mock_activate_plugin( string $path, bool $network_wide = false ): void {
		if ( $network_wide ) {
			if ( ! is_multisite() ) {
				throw new RuntimeException( 'Multisite is not enabled!, try running with slic run wpunit --env multisite' );
			}

			$current          = get_site_option( 'active_sitewide_plugins', [] );
			$current[ $path ] = time();

			update_site_option( 'active_sitewide_plugins', $current );

			return;
		}

		update_option(
			'active_plugins',
			array_merge( get_option( 'active_plugins', [] ), [ $path ] )
		);
	}

}
