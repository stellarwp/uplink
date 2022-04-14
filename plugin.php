<?php
/**
 * Plugin Name: StellarWP Plugin Delivery Network
 * Description: Integrates with the StellarWP Plugin Delivery Network service.
 * Author: StellarWP
 * Version: 1.0
 */

namespace StellarWP\PluginDelivery;

define('STELLAR_PDN_PATH', __DIR__);

require_once STELLAR_PDN_PATH . '/vendor/autoload.php';

Container::init()->register( Plugin::class );
