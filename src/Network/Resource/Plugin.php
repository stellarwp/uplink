<?php

namespace StellarWP\Network\Resource;

class Plugin extends Resource_Abstract {
	/**
	 * @inheritDoc
	 */
	protected $type = 'plugin';

	/**
	 * @inheritDoc
	 */
	public static function register( $name, $slug, $path, $class, $version ) {
		return parent::register_resource( static::class, $name, $slug, $path, $class, $version );
	}
}