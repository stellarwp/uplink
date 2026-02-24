<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Types;

/**
 * A Feature delivered as a standalone WordPress plugin ZIP.
 *
 * The download URL is resolved at install time through the existing
 * plugins_api filter, not stored on the object.
 *
 * @since TBD
 */
final class Zip extends Feature {

	/**
	 * The plugin file path relative to the plugins directory.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $plugin_file;

	/**
	 * Constructor for a Feature delivered as a standalone WordPress plugin ZIP.
	 *
	 * @since TBD
	 *
	 * @param string $slug        The feature slug.
	 * @param string $name        The feature display name.
	 * @param string $description The feature description.
	 * @param string $plugin_file The plugin file path (e.g. 'my-plugin/my-plugin.php').
	 *
	 * @return void
	 */
	public function __construct( string $slug, string $name, string $description, string $plugin_file ) {
		parent::__construct( $slug, $name, $description, 'zip' );

		$this->plugin_file = $plugin_file;
	}

	/**
	 * @inheritDoc
	 */
	public static function from_array( array $data ) {
		return new self(
			$data['slug'],
			$data['name'],
			$data['description'] ?? '',
			$data['plugin_file']
		);
	}

	/**
	 * Get the plugin file path relative to the plugins directory.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_plugin_file(): string {
		return $this->plugin_file;
	}
}
