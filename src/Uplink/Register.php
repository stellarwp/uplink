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
	 * @since 2.0.0 Added oAuth parameter.
	 *
	 * @param string $slug Resource slug.
	 * @param string $name Resource name.
	 * @param string $version Resource version.
	 * @param string $path Resource path to bootstrap file.
	 * @param string $class Resource class.
	 * @param string $license_class Resource license class.
	 * @param bool   $is_oauth Is the plugin using OAuth?
	 *
	 * @return Resources\Resource
	 */
	public static function plugin( $slug, $name, $version, $path, $class, $license_class = null, $is_oauth = false ) {
		return Resources\Plugin::register( $slug, $name, $version, $path, $class, $license_class, $is_oauth );
	}

	/**
	 * Register a service resource.
	 *
	 * @since 1.0.0
	 * @since 2.0.0 Added oAuth parameter.
	 *
	 * @param string $slug Resource slug.
	 * @param string $name Resource name.
	 * @param string $version Resource version.
	 * @param string $path Resource path to bootstrap file.
	 * @param string $class Resource class.
	 * @param string $license_class Resource license class.
	 * @param bool   $is_oauth Is the plugin using OAuth?
	 *
	 * @return Resources\Resource
	 */
	public static function service( $slug, $name, $version, $path, $class, $license_class = null, $is_oauth = false) {
		return Resources\Service::register( $slug, $name, $version, $path, $class, $license_class, $is_oauth );
	}
}
