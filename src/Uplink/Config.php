<?php

namespace StellarWP\Uplink;

use StellarWP\ContainerContract\ContainerInterface;

class Config {

	/**
	 * Container object.
	 *
	 * @since 1.0.0
	 *
	 * @var ContainerInterface
	 */
	protected static $container;

	/**
	 * Prefix for hook names.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected static $hook_prefix = '';

	/**
	 * Get the container.
	 *
	 * @return ContainerInterface
	 *@throws \RuntimeException
	 *
	 * @since 1.0.0
	 *
	 */
	public static function get_container() {
		if ( self::$container === null ) {
			throw new \RuntimeException( 'You must provide a container via StellarWP\Uplink\Config::set_container() before attempting to fetch it.' );
		}

		return self::$container;
	}

	/**
	 * Gets the hook prefix.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function get_hook_prefix() {
		return static::$hook_prefix;
	}

	/**
	 * Returns whether the container has been set.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function has_container() {
		return self::$container !== null;
	}

	/**
	 * Resets this class back to the defaults.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function reset() {
		static::$hook_prefix = '';
	}

	/**
	* Set the container object.
	*
	* @param ContainerInterface $container Container object.
	*
	* @return void
	*@since 1.0.0
	*
	*/
	public static function set_container( ContainerInterface $container ) {
		self::$container = $container;
	}

	/**
	 * Sets the hook prefix.
	 *
	 * @since 1.0.0
	 *
	 * @param string $prefix
	 *
	 * @return void
	 */
	public static function set_hook_prefix( string $prefix ) {
		// Make sure the prefix always ends with a separator.
		if ( substr( $prefix, -1 ) !== '/' ) {
			$prefix = $prefix . '/';
		}

		static::$hook_prefix = $prefix;
	}

}
