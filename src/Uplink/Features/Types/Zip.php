<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Types;

use StellarWP\Uplink\Utils\Cast;

/**
 * A Feature delivered as a standalone WordPress plugin ZIP.
 *
 * The download URL is resolved at install time through the existing
 * plugins_api filter, not stored on the object.
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
	 * Gets the new version available via plugins_api().
	 *
	 * The Handler filters the plugins_api response for Zip features,
	 * returning update data from the consolidation server.
	 *
	 * @since 3.0.0
	 *
	 * @return string|null The new version string, or null if unavailable.
	 */
	public function get_new_version(): ?string {
		$slug = $this->get_slug();

		if ( empty( $slug ) ) {
			return null;
		}

		if ( ! function_exists( 'plugins_api' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php'; // @phpstan-ignore-line -- ABSPATH exists.
		}

		$response = plugins_api(
			'plugin_information',
			[
				'fields' => [ 'sections' => false ],
				'slug'   => $slug,
			]
		);

		if ( is_wp_error( $response ) || ! is_object( $response ) ) {
			return null;
		}

		return Cast::to_string( $response->version ) ?? null;
	}
}
