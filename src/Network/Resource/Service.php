<?php

namespace StellarWP\Network\Resource;

class Service extends Resource_Abstract {
	/**
	 * @inheritDoc
	 */
	protected $type = 'service';

	/**
	 * @inheritDoc
	 */
	public static function register( $name, $slug, $path, $class, $version ) {
		return parent::register_resource( static::class, $name, $slug, $path, $class, $version );
	}
}
