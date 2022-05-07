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
	public static function register( $slug, $name, $version, $path, $class, $license_class = null ) {
		return parent::register_resource( static::class, $slug, $name, $version, $path, $class, $license_class );
	}
}
