<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features;

/**
 * WP_Error codes for the Features system.
 *
 * PHP 7.4 does not support native enums, so string
 * constants serve as the next-best compile-time guard.
 *
 * @since 3.0.0
 */
class Error_Code {

	/**
	 * A requested feature was not found in the catalog.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const FEATURE_NOT_FOUND = 'stellarwp-uplink-feature-not-found';

	/**
	 * A feature check failed due to an unexpected error.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const FEATURE_CHECK_FAILED = 'stellarwp-uplink-feature-check-failed';

	/**
	 * A feature request failed due to an unexpected error.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const FEATURE_REQUEST_FAILED = 'stellarwp-uplink-feature-request-failed';

	/**
	 * The feature catalog response was invalid or could not be parsed.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const INVALID_RESPONSE = 'stellarwp-uplink-feature-invalid-response';

	/**
	 * A feature was passed to a strategy that does not support its type.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const FEATURE_TYPE_MISMATCH = 'stellarwp-uplink-feature-type-mismatch';

	/**
	 * Plugin deactivation did not take effect.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const DEACTIVATION_FAILED = 'stellarwp-uplink-deactivation-failed';

	/**
	 * A concurrent install is already in progress for the same plugin.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const INSTALL_LOCKED = 'stellarwp-uplink-install-locked';

	/**
	 * The expected plugin file was not found on disk after installation.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const PLUGIN_NOT_FOUND_AFTER_INSTALL = 'stellarwp-uplink-plugin-not-found-after-install';

	/**
	 * The WordPress plugins_api() call failed.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const PLUGINS_API_FAILED = 'stellarwp-uplink-plugins-api-failed';

	/**
	 * No download link was returned by plugins_api() for the requested plugin.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const DOWNLOAD_LINK_MISSING = 'stellarwp-uplink-download-link-missing';

	/**
	 * The plugin installation failed.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const INSTALL_FAILED = 'stellarwp-uplink-install-failed';

	/**
	 * A fatal PHP error (Throwable) occurred during plugin activation.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const ACTIVATION_FATAL = 'stellarwp-uplink-activation-fatal';

	/**
	 * Plugin activation failed or did not take effect.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const ACTIVATION_FAILED = 'stellarwp-uplink-activation-failed';

	/**
	 * An installed plugin's author does not match the expected author(s).
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const PLUGIN_OWNERSHIP_MISMATCH = 'stellarwp-uplink-plugin-ownership-mismatch';

	/**
	 * The server's PHP or WordPress version does not meet the plugin's requirements.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const REQUIREMENTS_NOT_MET = 'stellarwp-uplink-requirements-not-met';

	/**
	 * The active theme cannot be disabled (WordPress always needs an active theme).
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const THEME_IS_ACTIVE = 'stellarwp-uplink-theme-is-active';

	/**
	 * The expected theme was not found on disk after installation.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const THEME_NOT_FOUND_AFTER_INSTALL = 'stellarwp-uplink-theme-not-found-after-install';

	/**
	 * The WordPress themes_api() call failed.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const THEMES_API_FAILED = 'stellarwp-uplink-themes-api-failed';

	/**
	 * An installed theme's author does not match the expected author(s).
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const THEME_OWNERSHIP_MISMATCH = 'stellarwp-uplink-theme-ownership-mismatch';

	/**
	 * A catalog feature has a type with no registered Feature subclass.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const UNKNOWN_FEATURE_TYPE = 'stellarwp-uplink-unknown-feature-type';

	/**
	 * Maps an error code to its recommended HTTP status code.
	 *
	 * @since 3.0.0
	 *
	 * @param string $code An Error_Code constant value.
	 *
	 * @return int The HTTP status code (defaults to 422 for unknown codes).
	 */
	public static function http_status( string $code ): int {
		/** @var array<string, int> */
		static $map = [
			// 400 Bad Request — the feature type cannot be handled by the resolved strategy.
			self::FEATURE_TYPE_MISMATCH          => 400,

			// 404 Not Found — the requested feature slug does not exist in the catalog.
			self::FEATURE_NOT_FOUND              => 404,

			// 409 Conflict — a concurrent install is in progress, an ownership
			// check failed, a deactivation was undone by another process,
			// or the active theme cannot be disabled.
			self::INSTALL_LOCKED                 => 409,
			self::PLUGIN_OWNERSHIP_MISMATCH      => 409,
			self::THEME_OWNERSHIP_MISMATCH       => 409,
			self::THEME_IS_ACTIVE                => 409,
			self::DEACTIVATION_FAILED            => 409,

			// 422 Unprocessable Entity — the request was understood but the operation
			// could not be completed (requirements not met, install/activation failure,
			// missing download link, or unexpected package structure).
			self::REQUIREMENTS_NOT_MET           => 422,
			self::INSTALL_FAILED                 => 422,
			self::ACTIVATION_FATAL               => 422,
			self::ACTIVATION_FAILED              => 422,
			self::PLUGIN_NOT_FOUND_AFTER_INSTALL => 422,
			self::THEME_NOT_FOUND_AFTER_INSTALL  => 422,
			self::DOWNLOAD_LINK_MISSING          => 422,
			self::UNKNOWN_FEATURE_TYPE           => 422,

			// 502 Bad Gateway — an upstream service (feature API, plugins_api) returned an error.
			self::INVALID_RESPONSE               => 502,
			self::FEATURE_CHECK_FAILED           => 502,
			self::FEATURE_REQUEST_FAILED         => 502,
			self::PLUGINS_API_FAILED             => 502,
			self::THEMES_API_FAILED              => 502,
		];

		// Default to 422 for unknown codes.
		return $map[ $code ] ?? 422;
	}
}
