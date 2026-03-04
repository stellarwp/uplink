<?php
/**
 * Plugin Name: Uplink Test - Deactivation Fatal
 * Description: A test plugin that re-activates itself on deactivation. Used to test the DEACTIVATION_FAILED error path.
 * Version: 1.0.0
 * Author: StellarWP
 * Requires PHP: 7.4
 * Requires at least: 5.8
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

register_deactivation_hook(
	__FILE__,
	function () {
		activate_plugin( plugin_basename( __FILE__ ) );
	}
);
