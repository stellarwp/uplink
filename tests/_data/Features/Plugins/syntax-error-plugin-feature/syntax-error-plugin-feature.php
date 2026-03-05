<?php
/**
 * Plugin Name: Uplink Test - Syntax Error
 * Description: A test plugin with a PHP syntax error. Used to test the ACTIVATION_FATAL error path with a ParseError.
 * Version: 1.0.0
 * Author: StellarWP
 * Requires PHP: 7.4
 * Requires at least: 5.8
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function uplink_test_syntax_error( {
	return 'this will never parse';
}
