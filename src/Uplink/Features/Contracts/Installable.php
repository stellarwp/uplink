<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Contracts;

/**
 * Contract for feature types that can be installed as WordPress extensions.
 *
 * Implemented by Zip (plugins) and Theme — not by Built_In.
 * Provides a uniform surface for Installable_Strategy template methods.
 *
 * @since 3.0.0
 */
interface Installable {

	/**
	 * Gets the primary WordPress identifier for this extension.
	 *
	 * For plugins: the plugin file path relative to the plugins directory
	 * (e.g. "stellar-export/stellar-export.php").
	 * For themes: the stylesheet (directory name).
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_wp_identifier(): string;

	/**
	 * Gets the extension slug (directory name).
	 *
	 * For plugins: dirname(plugin_file).
	 * For themes: the stylesheet.
	 *
	 * Used for transient lock keys and API lookups.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_extension_slug(): string;

	/**
	 * Gets the expected extension authors for ownership verification.
	 *
	 * @since 3.0.0
	 *
	 * @return string[]
	 */
	public function get_authors(): array;

	/**
	 * Whether this extension is available on WordPress.org.
	 *
	 * Prepares for future install-path branching (download_url vs .org repository).
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public function is_dot_org(): bool;
}
