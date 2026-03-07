<?php
/**
 * Plugin Name: Uplink Test - Die On Include
 * Description: A test plugin that calls die() on include. Used to test the uncatchable fatal scenario.
 * Version: 1.0.0
 * Author: StellarWP
 * Requires PHP: 7.4
 * Requires at least: 5.8
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

die( 'Intentional die() for Uplink Plugin Strategy testing.' );
