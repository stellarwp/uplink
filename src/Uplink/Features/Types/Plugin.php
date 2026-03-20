<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Types;

use StellarWP\Uplink\Catalog\Results\Catalog_Feature;
use StellarWP\Uplink\Features\Contracts\Installable;
use StellarWP\Uplink\Utils\Cast;

/**
 * A Feature delivered as a standalone WordPress plugin.
 *
 * The Plugin_Strategy installs the plugin via plugins_api() + Plugin_Upgrader,
 * and uses plugin_file (plugin file path) to activate/deactivate it.
 *
 * @since 3.0.0
 */
final class Plugin extends Feature implements Installable {

	/**
	 * Constructor for a Feature delivered as a standalone WordPress plugin.
	 *
	 * @since 3.0.0
	 *
	 * @param array<string, mixed> $attributes The feature attributes.
	 *
	 * @return void
	 */
	public function __construct( array $attributes ) {
		$attributes['type'] = self::TYPE_PLUGIN;

		$attributes = array_merge(
			$attributes,
			[
				'plugin_file'       => $attributes['plugin_file'] ?? '',
				'authors'           => $attributes['authors'] ?? [],
				'is_dot_org'        => $attributes['is_dot_org'] ?? false,
				'released_at'       => $attributes['released_at'] ?? null,
				'installed_version' => $attributes['installed_version'] ?? null,
				'version'           => $attributes['version'] ?? null,
				'changelog'         => $attributes['changelog'] ?? null,
			]
		);

		parent::__construct( $attributes );

		// has_update() reads $this->attributes, so it must be set after parent::__construct().
		$this->attributes['has_update'] = $this->has_update();
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
		return new self( $data );
	}

	/**
	 * Gets the plugin file path relative to the plugins directory
	 * (e.g. "stellar-export/stellar-export.php").
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
	 * Whether a newer version is available and this plugin is currently installed.
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

		$catalog_version = Cast::to_string( $this->attributes['version'] ?? '' );

		if ( $catalog_version === '' ) {
			return false;
		}

		return version_compare( $catalog_version, $installed_version, '>' );
	}

	/**
	 * Builds the complete update data array for this Plugin feature.
	 *
	 * @since 3.0.0
	 *
	 * @param Catalog_Feature $catalog_feature The catalog entry providing version and download URL.
	 *
	 * @return array<string, mixed>
	 */
	public function get_update_data( Catalog_Feature $catalog_feature ): array {
		return [
			'name'              => $this->get_name(),
			'slug'              => $this->get_slug(),
			'version'           => $catalog_feature->get_version() ?? '',
			'package'           => $catalog_feature->get_download_url() ?? '',
			'url'               => $this->get_documentation_url(),
			'author'            => implode( ', ', $this->get_authors() ),
			'sections'          => [
				'description' => $this->get_description(),
			],
			'plugin_file'       => $this->get_plugin_file(),
			'installed_version' => $this->get_installed_version() ?? '',
			'has_update'        => $this->has_update(),
		];
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

		// TODO: We should throw an error on object construction if plugin_file is not set for Plugin Features.
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

		$plugin_data = get_plugin_data( trailingslashit( WP_PLUGIN_DIR ) . $this->get_plugin_file() );

		return $plugin_data['Version'] ?? null;
	}
}
