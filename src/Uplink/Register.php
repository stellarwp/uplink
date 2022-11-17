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
	 * @param string $license_class Resource license class
	 *
	 * @return Resources\Resource
	 */
	public static function plugin( $name, $slug, $path, $class, $version, $license_class = null ) {
		return Resources\Plugin::register( $name, $slug, $path, $class, $version, $license_class );
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
	 * @param string $license_class Resource license class.
	 *
	 * @return Resources\Resource
	 */
	public static function service( $name, $slug, $path, $class, $version, $license_class = null ) {
		return Resources\Service::register( $name, $slug, $path, $class, $version, $license_class );
	}
}
