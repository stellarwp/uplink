<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Types;

use StellarWP\Uplink\Utils\Cast;

/**
 * A Feature delivered as a standalone WordPress plugin ZIP.
 *
 * The Zip_Strategy installs the plugin via plugins_api() + Plugin_Upgrader,
 * and uses plugin_file to activate/deactivate it.
 *
 * @since 3.0.0
 */
final class Zip extends Feature {

	/**
	 * Constructor for a Feature delivered as a standalone WordPress plugin ZIP.
	 *
	 * @since 3.0.0
	 *
	 * @param array<string, mixed> $attributes The feature attributes.
	 *
	 * @return void
	 */
	public function __construct( array $attributes ) {
		$attributes['type'] = 'zip';

		parent::__construct( $attributes );
	}

	/**
	 * Creates a Feature instance from an associative array.
	 *
	 * @since 3.0.0
	 *
	 * @param array<string, mixed> $data The feature data from the API response.
	 *
	 * @return static
	 */
	public static function from_array( array $data ) {
		return new self(
			[
				'slug'              => $data['slug'],
				'group'             => $data['group'],
				'tier'              => $data['tier'],
				'name'              => $data['name'],
				'description'       => $data['description'] ?? '',
				'type'              => 'zip',
				'plugin_file'       => $data['plugin_file'],
				'is_available'      => $data['is_available'],
				'documentation_url' => $data['documentation_url'] ?? '',
				'authors'           => $data['authors'] ?? [],
			]
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
		return Cast::to_string( $this->attributes['plugin_file'] ?? '' );
	}

	/**
	 * Gets the expected plugin authors for ownership verification.
	 *
	 * @since 3.0.0
	 *
	 * @return string[]
	 */
	public function get_authors(): array {
		$authors = $this->attributes['authors'] ?? [];

		return is_array( $authors ) ? $authors : [];
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
		return dirname( $this->get_plugin_file() );
	}
}
