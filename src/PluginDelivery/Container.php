<?php
namespace StellarWP\PluginDelivery;

class Container extends \tad_DI52_Container {
	/**
	 * @var Container
	 */
	protected static $instance;

	/**
	 * @return Container
	 */
	public static function init() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}
}
