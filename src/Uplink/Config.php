<?php declare( strict_types=1 );

namespace StellarWP\Uplink;

use InvalidArgumentException;
use RuntimeException;
use StellarWP\ContainerContract\ContainerInterface;
use StellarWP\Uplink\Auth\Token\Contracts\Token_Manager;
use StellarWP\Uplink\Utils\Sanitize;

class Config {

	public const TOKEN_OPTION_NAME = 'uplink.token_prefix';

	/**
	 * The default authorization cache time in seconds (6 hours).
	 */
	public const DEFAULT_AUTH_CACHE = 21600;

	/**
	 * The default state for all multisite licensing options.
	 *
	 * @var bool
	 */
	public const DEFAULT_MULTISITE_STATE = false;

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
	 * How long in seconds we cache successful authorization
	 * token requests.
	 *
	 * @var int
	 */
	protected static $auth_cache_expiration = self::DEFAULT_AUTH_CACHE;

	/**
	 * Whether your plugin allows multisite network subfolder licenses.
	 *
	 * @var bool
	 */
	protected static $supports_site_level_licenses_for_subfolder_multisite = self::DEFAULT_MULTISITE_STATE;

	/**
	 * Whether your plugin allows multisite subdomain licenses.
	 *
	 * @var bool
	 */
	protected static $supports_site_level_licenses_for_subdomain_multisite = self::DEFAULT_MULTISITE_STATE;

	/**
	 * Whether your plugin allows multisite domain mapping licenses.
	 *
	 * @var bool
	 */
	protected static $supports_site_level_licenses_for_mapped_domain_multisite = self::DEFAULT_MULTISITE_STATE;

	/**
	 * If true, enables a checkbox in the License Field so that you can use a local license key
	 * in place of the network key.
	 *
	 * @var bool
	 */
	protected static $supports_site_level_override_for_multisite_license = self::DEFAULT_MULTISITE_STATE;

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
			throw new RuntimeException(
				__( 'You must provide a container via StellarWP\Uplink\Config::set_container() before attempting to fetch it.', '%TEXTDOMAIN%' )
			);
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
			throw new RuntimeException(
				__( 'You must provide a hook prefix via StellarWP\Uplink\Config::set_hook_prefix() before attempting to fetch it.', '%TEXTDOMAIN%' )
			);
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
			throw new RuntimeException(
				__( 'You must provide a hook prefix via StellarWP\Uplink\Config::set_hook_prefix() before attempting to fetch it.', '%TEXTDOMAIN%' )
			);
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
		static::$hook_prefix                                              = '';
		static::$auth_cache_expiration                                    = self::DEFAULT_AUTH_CACHE;
		static::$supports_site_level_licenses_for_subfolder_multisite     = self::DEFAULT_MULTISITE_STATE;
		static::$supports_site_level_licenses_for_subdomain_multisite     = self::DEFAULT_MULTISITE_STATE;
		static::$supports_site_level_licenses_for_mapped_domain_multisite = self::DEFAULT_MULTISITE_STATE;
		static::$supports_site_level_override_for_multisite_license       = self::DEFAULT_MULTISITE_STATE;

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
			throw new RuntimeException(
				__( 'You must set a container with StellarWP\Uplink\Config::set_container() before setting a token auth prefix.', '%TEXTDOMAIN%' )
			);
		}

		$prefix = Sanitize::sanitize_title_with_hyphens( rtrim( $prefix, '_' ) );
		$key    = sprintf( '%s_%s', $prefix, Token_Manager::TOKEN_SUFFIX );

		// The option_name column in wp_options is a varchar(191)
		$max_length = 191;

		if ( strlen( $key ) > $max_length ) {
			throw new InvalidArgumentException(
				sprintf(
					__( 'The token auth prefix must be at most %d characters, including a trailing hyphen.', '%TEXTDOMAIN%' ),
					absint( $max_length - strlen( Token_Manager::TOKEN_SUFFIX ) )
				)
			);
		}

		self::get_container()->singleton( self::TOKEN_OPTION_NAME, $key );
	}

	/**
	 * Set the token authorization expiration.
	 *
	 * @param  int  $seconds  The time seconds the cache will exist for.
	 *                        -1 = disabled, 0 = no expiration.
	 *
	 * @return void
	 */
	public static function set_auth_cache_expiration( int $seconds ): void {
		static::$auth_cache_expiration = $seconds;
	}

	/**
	 * Get the token authorization expiration.
	 *
	 * @return int
	 */
	public static function get_auth_cache_expiration(): int {
		return static::$auth_cache_expiration;
	}

	/**
	 * Allow or disallow multisite subfolder licenses at the network level.
	 *
	 * @param  bool  $allowed
	 *
	 * @return void
	 */
	public static function allow_site_level_licenses_for_subfolder_multisite( bool $allowed ): void {
		static::$supports_site_level_licenses_for_subfolder_multisite = $allowed;
	}

	/**
	 * Allow or disallow multisite subdomain licenses at the network level.
	 *
	 * @param  bool  $allowed
	 *
	 * @return void
	 */
	public static function allow_site_level_licenses_for_subdomain_multisite( bool $allowed ): void {
		static::$supports_site_level_licenses_for_subdomain_multisite = $allowed;
	}

	/**
	 * Allow or disallow multisite domain mapping licenses at the network level.
	 *
	 * @param  bool  $allowed
	 *
	 * @return void
	 */
	public static function allow_site_level_licenses_for_mapped_domain_multisite( bool $allowed ): void {
		static::$supports_site_level_licenses_for_mapped_domain_multisite = $allowed;
	}

	/**
	 * Whether your plugin allows multisite network subfolder licenses.
	 *
	 * @throws RuntimeException
	 *
	 * @return bool
	 */
	public static function supports_site_level_licenses_for_subfolder_multisite(): bool {
		return (bool) apply_filters(
			'stellarwp/uplink/' . Config::get_hook_prefix() . '/supports_site_level_licenses_for_subfolder_multisite',
			static::$supports_site_level_licenses_for_subfolder_multisite
		);
	}

	/**
	 * Whether your plugin allows multisite network subdomain licenses.
	 *
	 * @throws RuntimeException
	 *
	 * @return bool
	 */
	public static function supports_site_level_licenses_for_subdomain_multisite(): bool {
		return (bool) apply_filters(
			'stellarwp/uplink/' . Config::get_hook_prefix() . '/supports_site_level_licenses_for_subdomain_multisite',
			static::$supports_site_level_licenses_for_subdomain_multisite
		);
	}

	/**
	 * Whether your plugin allows multisite network domain mapping licenses.
	 *
	 * @throws RuntimeException
	 *
	 * @return bool
	 */
	public static function supports_site_level_licenses_for_mapped_domain_multisite(): bool {
		return (bool) apply_filters(
			'stellarwp/uplink/' . Config::get_hook_prefix() . '/supports_site_level_licenses_for_mapped_domain_multisite',
			static::$supports_site_level_licenses_for_mapped_domain_multisite
		);
	}

	/**
	 * Check if any of the network licencing options are enabled.
	 *
	 * @throws RuntimeException
	 *
	 * @return bool
	 */
	public static function supports_network_licenses(): bool {
		$config = [
			static::supports_site_level_licenses_for_subfolder_multisite(),
			static::supports_site_level_licenses_for_subdomain_multisite(),
			static::supports_site_level_licenses_for_mapped_domain_multisite(),
		];

		return in_array( true, $config, true );
	}

	/**
	 * Enables a checkbox in the License Field so that you can use a local license key in place of
	 * the network key.
	 *
	 * @param  bool  $allowed
	 *
	 * @return void
	 */
	public static function allow_site_level_override_for_multisite_license( bool $allowed ): void {
		static::$supports_site_level_override_for_multisite_license = $allowed;
	}

	/**
	 * If this instance allows site level license key overrides.
	 *
	 * @return bool
	 */
	public static function supports_site_level_override_for_multisite_license(): bool {
		return (bool) apply_filters(
			'stellarwp/uplink/' . Config::get_hook_prefix() . '/supports_site_level_override_for_multisite_license',
			static::$supports_site_level_override_for_multisite_license
		);
	}

}
