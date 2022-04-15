<?php
/**
 * Plugin Name: StellarWP Plugin Delivery
 * Description: Integrates with the StellarWP Plugin Delivery service.
 * Author: StellarWP
 * Author URI: https://evnt.is/1x
 * Version: 1.0
 * Text Domain: stellar-pdc
 * License: GPLv2 or later
 */

namespace StellarWP\PluginDelivery;

define( 'STELLAR_PDC_PATH', __DIR__ );

require_once STELLAR_PDC_PATH . '/vendor/autoload.php';

Container::init()->register( Plugin::class );
