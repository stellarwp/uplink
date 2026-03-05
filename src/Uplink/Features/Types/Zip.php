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
				'plugin_file'       => $data['plugin_file'] ?? '',
				'plugin_slug'       => $data['plugin_slug'] ?? '',
				'is_available'      => $data['is_available'],
				'documentation_url' => $data['documentation_url'] ?? '',
				'new_version'       => $data['new_version'] ?? null,
				'authors'           => $data['authors'] ?? [],
			]
		);
	}

	/**
	 * Converts the feature to an associative array.
	 *
	 * @since 3.0.0
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return parent::to_array() + [
			'installed_version' => $this->get_installed_version(),
			'new_version'       => $this->get_new_version(),
			'has_update'        => $this->has_update(),
		];
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
	 * Gets the download URL for this Zip feature.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_download_url(): string {
		return Cast::to_string( $this->attributes['download_url'] ?? '' );
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
		return dirname( $this->get_plugin_file() );
	}

	/**
	 * Checks whether this Zip feature's plugin is currently installed.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public function is_installed(): bool {
		$plugin_file = $this->get_plugin_file();

		if ( empty( $plugin_file ) ) {
			return false;
		}

		return file_exists( trailingslashit( WP_PLUGIN_DIR ) . $plugin_file );
	}

	/**
	 * Gets the currently installed version of this Zip feature's plugin.
	 * Returns null if the plugin is not installed.
	 *
	 * @since 3.0.0
	 *
	 * @return string|null
	 */
	public function get_installed_version(): ?string {
		if ( ! $this->is_installed() ) {
			return null;
		}

		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php'; // @phpstan-ignore-line -- ABSPATH exists.
		}

		$plugin_data = get_plugin_data( trailingslashit( WP_PLUGIN_DIR ) . $this->get_plugin_file() ); // @phpstan-ignore-line

		return $plugin_data['Version'] ?? null;
	}

	/**
	 * Checks whether an update is available for this Zip feature's plugin.
	 *
	 * Reads the update_plugins site transient populated by the consolidated
	 * update Handler, and compares the new version against the installed version.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public function has_update(): bool {
		$installed_version = $this->get_installed_version();

		if ( $installed_version === null ) {
			return false;
		}

		$new_version = $this->get_new_version();

		if ( $new_version === null ) {
			return false;
		}

		return version_compare( $new_version, $installed_version, '>' );
	}

	/**
	 * Gets the newest available version.
	 *
	 * Prefers the version from the catalog API response. Falls back to
	 * reading from the update_plugins site transient populated by the
	 * consolidated update Handler.
	 *
	 * @since 3.0.0
	 *
	 * @return string|null The new version string, or null if unavailable.
	 */
	public function get_new_version(): ?string {
		$version = $this->attributes['new_version'] ?? null;

		if ( $version !== null ) {
			return Cast::to_string( $version );
		}

		return $this->get_new_version_from_transient();
	}

	/**
	 * Gets the newest available version from the update_plugins site transient.
	 *
	 * Reads the transient that WordPress populates via
	 * pre_set_site_transient_update_plugins (filtered by the Handler).
	 * This avoids per-feature plugins_api() calls which are expensive.
	 *
	 * @since 3.0.0
	 *
	 * @return string|null The new version string, or null if unavailable.
	 */
	private function get_new_version_from_transient(): ?string {
		/**
		 * Prevent infinite recursion: when our Handler filters
		 * site_transient_update_plugins (GET), it calls fetch_updates()
		 * which iterates Zip features and may reach this method.
		 * Reading the transient here would re-trigger the GET filter.
		 */
		if ( doing_filter( 'site_transient_update_plugins' ) ) {
			return null;
		}

		$plugin_file = $this->get_plugin_file();

		if ( empty( $plugin_file ) ) {
			return null;
		}

		$transient = get_site_transient( 'update_plugins' );

		if ( ! is_object( $transient ) ) {
			return null;
		}

		$update = $transient->response[ $plugin_file ] ?? $transient->no_update[ $plugin_file ] ?? null; // @phpstan-ignore property.notFound, offsetAccess.nonOffsetAccessible

		if ( ! is_object( $update ) ) {
			return null;
		}

		$version = $update->new_version ?? null; // @phpstan-ignore property.notFound

		return $version !== null ? Cast::to_string( $version ) : null;
	}
}
