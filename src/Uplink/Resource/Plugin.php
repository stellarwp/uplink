<?php

namespace StellarWP\Uplink\Resource;

class Plugin extends Resource_Abstract {
	/**
	 * @inheritDoc
	 */
	protected $type = 'plugin';

	/**
	 * @inheritDoc
	 */
	public static function register( $slug, $name, $version, $path, $class, string $license_class = null ) {
		return parent::register_resource( static::class, $slug, $name, $version, $path, $class, $license_class );
	}
}
