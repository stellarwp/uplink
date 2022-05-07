<?php
/**
 * Plugin Name: StellarWP Network
 * Description: Adds integration with the StellarWP Plugin Network.
 * Author: StellarWP
 * Author URI: https://stellarwp.com
 * Version: 1.0
 * Text Domain: stellar-network
 * License: GPLv2 or later
 */

namespace StellarWP\Network;

define( 'STELLAR_NETWORK_PATH', __DIR__ );

require_once STELLAR_NETWORK_PATH . '/vendor/autoload.php';

add_action( 'plugins_loaded', function() {
	Network::init();
}, 0 );
