<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Types;

use StellarWP\Uplink\Features\Contracts\Installable;
use StellarWP\Uplink\Utils\Cast;

/**
 * A Feature delivered as a standalone WordPress plugin ZIP.
 *
 * The Zip_Strategy installs the plugin via plugins_api() + Plugin_Upgrader,
 * and uses plugin_file to activate/deactivate it.
 *
 * @since 3.0.0
 */
final class Zip extends Feature implements Installable {

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
		return new self( array_merge( self::base_attributes( $data ), [
			'plugin_file' => $data['plugin_file'] ?? '',
			'plugin_slug' => $data['plugin_slug'] ?? '',
			'authors'     => $data['authors'] ?? [],
			'is_dot_org'  => $data['is_dot_org'] ?? false,
		] ) );
	}

	/**
	 * Gets the primary WordPress identifier — the plugin file path
	 * relative to the plugins directory (e.g. "stellar-export/stellar-export.php").
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_wp_identifier(): string {
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

		if ( ! is_array( $authors ) ) {
			return [];
		}

		return array_values( array_filter( $authors, 'is_string' ) );
	}

	/**
	 * Whether this plugin is available on WordPress.org.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public function is_dot_org(): bool {
		return Cast::to_bool( $this->attributes['is_dot_org'] ?? false );
	}

	/**
	 * Gets the plugin slug used for plugins_api() lookups and transient locks.
	 *
	 * This may differ from the plugin directory name. For example, TEC plugins
	 * and StellarSites use slugs that don't match their directory names.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_plugin_slug(): string {
		return Cast::to_string( $this->attributes['plugin_slug'] ?? '' );
	}

	/**
	 * Gets the plugin directory name derived from the plugin file path.
	 *
	 * For "stellar-export/stellar-export.php" this returns "stellar-export".
	 * Used for filesystem operations and ownership checks.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_plugin_directory(): string {
		return dirname( $this->get_wp_identifier() );
	}
}
