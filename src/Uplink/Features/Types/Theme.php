<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Types;

use StellarWP\Uplink\Catalog\Results\Catalog_Feature;
use StellarWP\Uplink\Features\Contracts\Installable;
use StellarWP\Uplink\Utils\Cast;

/**
 * A Feature delivered as a WordPress theme.
 *
 * The Theme_Strategy installs the theme via themes_api() + Theme_Upgrader,
 * and uses the stylesheet (directory name) to switch/detect the active theme.
 *
 * @since 3.0.0
 */
final class Theme extends Feature implements Installable {

	/**
	 * Constructor for a Feature delivered as a WordPress theme.
	 *
	 * @since 3.0.0
	 *
	 * @param array<string, mixed> $attributes The feature attributes.
	 *
	 * @return void
	 */
	public function __construct( array $attributes ) {
		$attributes['type'] = self::TYPE_THEME;

		parent::__construct( $attributes );
	}

	/**
	 * Creates a Theme instance from an associative array.
	 *
	 * @since 3.0.0
	 *
	 * @param array<string, mixed> $data The feature data from the API response.
	 *
	 * @return static
	 */
	public static function from_array( array $data ) {
		return new self(
			array_merge(
				self::base_attributes( $data ),
				[
					'authors'           => $data['authors'] ?? [],
					'is_dot_org'        => $data['is_dot_org'] ?? false,
					'released_at'       => $data['released_at'] ?? null,
					'installed_version' => $data['installed_version'] ?? null,
					'version'           => $data['version'] ?? null,
					'changelog'         => $data['changelog'] ?? null,
				]
			)
		);
	}

	/**
	 * Gets the expected theme authors for ownership verification.
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
	 * Whether this theme is available on WordPress.org.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public function is_dot_org(): bool {
		return Cast::to_bool( $this->attributes['is_dot_org'] ?? false );
	}

	/**
	 * Builds the complete update data array for this Theme feature.
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
			'installed_version' => $this->get_installed_version() ?? '',
		];
	}

	/**
	 * Checks whether this theme feature is currently installed.
	 *
	 * Uses the feature slug as the stylesheet (directory name) to check
	 * whether the theme exists on disk.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public function is_installed(): bool {
		if ( ! function_exists( 'wp_get_theme' ) ) {
			return false;
		}

		return wp_get_theme( $this->get_slug() )->exists();
	}

	/**
	 * Gets the currently installed version of this theme feature.
	 * Returns null if the theme is not installed.
	 *
	 * @since 3.0.0
	 *
	 * @return string|null
	 */
	public function get_installed_version(): ?string {
		if ( ! $this->is_installed() ) {
			return null;
		}

		$theme   = wp_get_theme( $this->get_slug() );
		$version = $theme->get( 'Version' );

		return is_string( $version ) && $version !== '' ? $version : null;
	}
}
