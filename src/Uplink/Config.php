<?php

namespace StellarWP\Uplink;

use InvalidArgumentException;
use RuntimeException;
use StellarWP\ContainerContract\ContainerInterface;
use StellarWP\Uplink\Auth\Token\Contracts\Token_Manager;
use StellarWP\Uplink\Utils\Sanitize;

class Config {

	public const TOKEN_OPTION_NAME = 'uplink.token_prefix';


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
	 * @since 1.0.0
	 *
	 * @throws RuntimeException
	 *
	 * @return ContainerInterface
	 */
	public static function get_container() {
		if ( self::$container === null ) {
			throw new RuntimeException( 'You must provide a container via StellarWP\Uplink\Config::set_container() before attempting to fetch it.' );
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
	public static function get_hook_prefix(): string {
		if ( self::$hook_prefix === null ) {
			throw new RuntimeException( 'You must provide a hook prefix via StellarWP\Uplink\Config::set_hook_prefix() before attempting to fetch it.' );
		}

		return static::$hook_prefix;
	}

	/**
	 * Gets the hook underscored prefix.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function get_hook_prefix_underscored(): string {
		if ( self::$hook_prefix === null ) {
			throw new RuntimeException( 'You must provide a hook prefix via StellarWP\Uplink\Config::set_hook_prefix() before attempting to fetch it.' );
		}

		return strtolower( str_replace( '-', '_', sanitize_title( static::$hook_prefix ) ) );
	}

	/**
	 * Returns whether the container has been set.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function has_container(): bool {
		return self::$container !== null;
	}

	/**
	 * Resets this class back to the defaults.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function reset(): void {
		static::$hook_prefix = '';

		if ( self::has_container() ) {
			self::$container->singleton( self::TOKEN_OPTION_NAME, null );
		}
	}

	/**
	* Set the container object.
	*
    * @since 1.0.0
    *
	* @param ContainerInterface $container Container object.
	*
	* @return void
	*/
	public static function set_container( ContainerInterface $container ): void {
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
	public static function set_hook_prefix( string $prefix ): void {
		static::$hook_prefix = $prefix;
	}

	/**
	 * Sets a token options table prefix for storing an origin's authorization token.
	 *
	 * This should be the same across all of your products.
	 *
	 * @since 1.3.0
	 *
	 * @param  string  $prefix
	 *
	 * @throws RuntimeException|InvalidArgumentException
	 *
	 * @return void
	 */
	public static function set_token_auth_prefix( string $prefix ): void {
		if ( ! self::has_container() ) {
			throw new RuntimeException( 'You must set a container with StellarWP\Uplink\Config::set_container() before setting a token auth prefix.' );
		}

		$prefix = Sanitize::sanitize_title_with_hyphens( rtrim( $prefix, '_' ) );
		$key    = sprintf( '%s_%s', $prefix, Token_Manager::TOKEN_SUFFIX );

		// The option_name column in wp_options is a varchar(191)
		$max_length = 191;

		if ( strlen( $key ) > $max_length ) {
			throw new InvalidArgumentException(
				sprintf(
					'The token auth prefix must be at most %d characters, including a trailing hyphen.',
					absint( $max_length - strlen( Token_Manager::TOKEN_SUFFIX ) )
				)
			);
		}

		self::$container->singleton( self::TOKEN_OPTION_NAME, $key );
	}

}
