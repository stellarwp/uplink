<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features;

/**
 * A feature backed by a WordPress plugin distributed as a ZIP file.
 *
 * The Zip_Strategy uses download_url to install the plugin via Plugin_Upgrader,
 * and plugin_file to activate/deactivate it. The download_url is always
 * provided by the catalog API — no plugins_api() lookup is needed.
 *
 * @since 3.0.0
 */
class Zip_Feature extends Feature {

	/**
	 * Relative path to the plugin's main file from the plugins directory.
	 *
	 * Must match the directory name inside the ZIP, e.g.:
	 * "stellar-export/stellar-export.php"
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	protected $plugin_file;

	/**
	 * Direct download URL for the plugin ZIP.
	 *
	 * Provided by the catalog API. May include authentication tokens as
	 * query parameters. Example:
	 * "https://portal.stellarwp.com/stellar-export.zip?secret=abc123".
	 *
	 * TODO: Update this comment later with the actual download URL format.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	protected $download_url;

	/**
	 * Expected plugin authors for ownership verification.
	 *
	 * The Zip_Strategy compares these values against the installed plugin's
	 * Author header to ensure we don't activate a different developer's
	 * plugin that happens to share the same directory slug. Multiple values
	 * support cases where a plugin's author name may vary (e.g. rebrand).
	 *
	 * @since 3.0.0
	 *
	 * @var string[]
	 */
	protected $authors;

	/**
	 * Construct a Zip_Feature with plugin file path and download URL.
	 *
	 * Hard-codes the type to "zip" so the Manager can dispatch to Zip_Strategy.
	 *
	 * @since 3.0.0
	 *
	 * @param string   $slug         Unique feature identifier (e.g. "stellar-export").
	 * @param string   $name         Human-readable display name.
	 * @param string   $description  Brief description of the feature.
	 * @param string   $plugin_file  Relative plugin file path (e.g. "stellar-export/stellar-export.php").
	 * @param string   $download_url Direct download URL for the plugin ZIP.
	 * @param string[] $authors      Expected plugin authors for ownership verification.
	 */
	public function __construct(
		string $slug,
		string $name,
		string $description,
		string $plugin_file,
		string $download_url,
		array $authors
	) {
		parent::__construct( $slug, $name, $description, 'zip' );

		$this->plugin_file  = $plugin_file;
		$this->download_url = $download_url;
		$this->authors      = $authors;
	}

	/**
	 * Get the relative path to the plugin's main file.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_plugin_file(): string {
		return $this->plugin_file;
	}

	/**
	 * Get the direct download URL for the plugin ZIP.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_download_url(): string {
		return $this->download_url;
	}

	/**
	 * Get the expected plugin authors for ownership verification.
	 *
	 * @since 3.0.0
	 *
	 * @return string[]
	 */
	public function get_authors(): array {
		return $this->authors;
	}

	/**
	 * Get the plugin slug (directory name) derived from the plugin file path.
	 *
	 * For "stellar-export/stellar-export.php" this returns "stellar-export".
	 * Used as a unique identifier for transient locks and directory checks.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_plugin_slug(): string {
		return dirname( $this->plugin_file );
	}

}
