<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Types;

/**
 * A Feature delivered as a standalone WordPress plugin ZIP.
 *
 * The Zip_Strategy uses download_url to install the plugin via Plugin_Upgrader,
 * and plugin_file to activate/deactivate it. The download_url is always
 * provided by the catalog API.
 *
 * @since TBD
 */
final class Zip extends Feature {

	/**
	 * The plugin file path relative to the plugins directory.
	 *
	 * Must match the directory name inside the ZIP, e.g.:
	 * "stellar-export/stellar-export.php"
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $plugin_file;

	/**
	 * Direct download URL for the plugin ZIP.
	 *
	 * Provided by the catalog API. May include authentication tokens as
	 * query parameters.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $download_url;

	/**
	 * Expected plugin authors for ownership verification.
	 *
	 * The Zip_Strategy compares these values against the installed plugin's
	 * Author header to ensure we don't activate a different developer's
	 * plugin that happens to share the same directory slug.
	 *
	 * @since TBD
	 *
	 * @var string[]
	 */
	protected array $authors;

	/**
	 * Constructor for a Feature delivered as a standalone WordPress plugin ZIP.
	 *
	 * @since TBD
	 *
	 * @param string   $slug         The feature slug.
	 * @param string   $name         The feature display name.
	 * @param string   $description  The feature description.
	 * @param string   $plugin_file  The plugin file path (e.g. 'my-plugin/my-plugin.php').
	 * @param string   $download_url Direct download URL for the plugin ZIP.
	 * @param string[] $authors      Expected plugin authors for ownership verification.
	 *
	 * @return void
	 */
	public function __construct(
		string $slug,
		string $name,
		string $description,
		string $plugin_file,
		string $download_url = '',
		array $authors = []
	) {
		parent::__construct( $slug, $name, $description, 'zip' );

		$this->plugin_file  = $plugin_file;
		$this->download_url = $download_url;
		$this->authors      = $authors;
	}

	/**
	 * @inheritDoc
	 */
	public static function from_array( array $data ) {
		return new self(
			$data['slug'],
			$data['name'],
			$data['description'] ?? '',
			$data['plugin_file'],
			$data['download_url'] ?? '',
			$data['authors'] ?? []
		);
	}

	/**
	 * Gets the plugin file path relative to the plugins directory.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_plugin_file(): string {
		return $this->plugin_file;
	}

	/**
	 * Gets the direct download URL for the plugin ZIP.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_download_url(): string {
		return $this->download_url;
	}

	/**
	 * Gets the expected plugin authors for ownership verification.
	 *
	 * @since TBD
	 *
	 * @return string[]
	 */
	public function get_authors(): array {
		return $this->authors;
	}

	/**
	 * Gets the plugin slug (directory name) derived from the plugin file path.
	 *
	 * For "stellar-export/stellar-export.php" this returns "stellar-export".
	 * Used as a unique identifier for transient locks and directory checks.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_plugin_slug(): string {
		return dirname( $this->plugin_file );
	}
}
