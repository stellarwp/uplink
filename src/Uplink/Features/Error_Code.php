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
}
