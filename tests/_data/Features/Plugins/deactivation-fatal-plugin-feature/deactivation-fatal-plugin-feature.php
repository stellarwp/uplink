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

// Hook into update_option_active_plugins instead of register_deactivation_hook.
// WordPress's deactivate_plugins() fetches the active list BEFORE firing deactivation
// hooks, then overwrites the option AFTER. So calling activate_plugin() inside a
// deactivation hook gets clobbered. This hook fires when the option is actually
// written, letting us add ourselves back after the overwrite.
add_action(
	'update_option_active_plugins',
	static function ( $old_value, $value ) {
		$basename = plugin_basename( __FILE__ );

		if ( in_array( $basename, $old_value, true ) && ! in_array( $basename, $value, true ) ) {
			$value[] = $basename;
			remove_all_actions( 'update_option_active_plugins' );
			update_option( 'active_plugins', $value );
		}
	},
	10,
	2
);
