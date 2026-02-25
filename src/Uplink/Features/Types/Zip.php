<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Types;

/**
 * A Feature delivered as a standalone WordPress plugin ZIP.
 *
 * The Zip_Strategy uses download_url to install the plugin via Plugin_Upgrader,
 * and plugin_file to activate/deactivate it. The download_url is always
 * provided by the catalog API.
 *
 * @since 3.0.0
 */
final class Zip extends Feature {

	/**
	 * The plugin file path relative to the plugins directory.
	 *
	 * @since 3.0.0
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
	 * plugin that happens to share the same directory slug.
	 *
	 * @since 3.0.0
	 *
	 * @var string[]
	 */
	protected $authors;

	/**
	 * Constructor for a Feature delivered as a standalone WordPress plugin ZIP.
	 *
	 * @since 3.0.0
	 *
	 * @param string   $slug          The feature slug.
	 * @param string   $group         The product group (e.g. 'LearnDash', 'TEC').
	 * @param string   $tier          The feature tier (e.g. 'Tier 1', 'Tier 2').
	 * @param string   $name          The feature display name.
	 * @param string   $description   The feature description.
	 * @param string   $plugin_file   The plugin file path (e.g. 'my-plugin/my-plugin.php').
	 * @param bool     $is_available  Whether the feature is available.
	 * @param string   $documentation The URL to the feature documentation.
	 * @param string   $download_url  Direct download URL for the plugin ZIP.
	 * @param string[] $authors       Expected plugin authors for ownership verification.
	 *
	 * @return void
	 */
	public function __construct(
		string $slug,
		string $group,
		string $tier,
		string $name,
		string $description,
		string $plugin_file,
		bool $is_available,
		string $documentation = '',
		string $download_url = '',
		array $authors = []
	) {
		parent::__construct( $slug, $group, $tier, $name, $description, 'zip', $is_available, $documentation );

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
			$data['group'],
			$data['tier'],
			$data['name'],
			$data['description'] ?? '',
			$data['plugin_file'],
			$data['is_available'],
			$data['documentation'] ?? '',
			$data['download_url'] ?? '',
			$data['authors'] ?? []
		);
	}

	/**
	 * Gets the plugin file path relative to the plugins directory.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_plugin_file(): string {
		return $this->plugin_file;
	}

	/**
	 * Gets the direct download URL for the plugin ZIP.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_download_url(): string {
		return $this->download_url;
	}

	/**
	 * Gets the expected plugin authors for ownership verification.
	 *
	 * @since 3.0.0
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
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_plugin_slug(): string {
		return dirname( $this->plugin_file );
	}
}
