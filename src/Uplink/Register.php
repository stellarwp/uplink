<?php

namespace StellarWP\Uplink;

/**
 * A helper class for registering StellarWP Uplink resources.
 */
class Register {
	/**
	 * Register a plugin resource.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Resource name.
	 * @param string $slug Resource slug.
	 * @param string $path Resource path to bootstrap file.
	 * @param string $class Resource class.
	 * @param string $version Resource version.
	 *
	 * @return Resource\Resource_Abstract
	 */
	public static function plugin( $name, $slug, $path, $class, $version ) {
		return Resource\Plugin::register( $name, $slug, $path, $class, $version );
	}

	/**
	 * Register a service resource.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Resource name.
	 * @param string $slug Resource slug.
	 * @param string $path Resource path to bootstrap file.
	 * @param string $class Resource class.
	 * @param string $version Resource version.
	 *
	 * @return Resource\Resource_Abstract
	 */
	public static function service( $name, $slug, $path, $class, $version ) {
		return Resource\Service::register( $name, $slug, $path, $class, $version );
	}
}
