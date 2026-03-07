<?php
/**
 * Plugin Name: Uplink Test - Fatal Plugin Feature
 * Description: A test plugin that throws a fatal error on activation. Used to test the ACTIVATION_FATAL error path.
 * Version: 1.0.0
 * Author: StellarWP
 * Requires PHP: 7.4
 * Requires at least: 5.8
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

throw new \RuntimeException( 'Intentional fatal error for Uplink Plugin Strategy testing.' );
