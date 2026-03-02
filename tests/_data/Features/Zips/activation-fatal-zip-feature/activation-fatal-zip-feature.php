<?php
/**
 * Plugin Name: Uplink Test - Activation Fatal
 * Description: A test plugin that throws a fatal error in its activation hook. Used to test the ACTIVATION_FATAL error path.
 * Version: 1.0.0
 * Author: StellarWP
 * Requires PHP: 7.4
 * Requires at least: 5.8
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

register_activation_hook(
	__FILE__,
	function () {
		throw new \RuntimeException( 'Intentional activation hook fatal for Uplink Zip Strategy testing.' );
	}
);
