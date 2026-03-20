<?php
/**
 * Plugin Name: Uplink Test Bootstrap
 * Description: Bootstraps StellarWP Uplink during plugins_loaded, before wp_loaded fires.
 * Version: 1.0.0
 * Author: StellarWP
 */

use StellarWP\ContainerContract\ContainerInterface;
use StellarWP\Uplink\Config;
use StellarWP\Uplink\Tests\Container;
use StellarWP\Uplink\Uplink;

add_action(
	'plugins_loaded',
	static function () {
		$container = new Container();
		$container->singleton( ContainerInterface::class, $container );
		Config::set_container( $container );
		Config::set_hook_prefix( 'test' );
		Uplink::init();
	},
	0
);
