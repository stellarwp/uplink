<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Catalog\Results;

use StellarWP\Uplink\Utils\Cast;

/**
 * A single feature entry from the product catalog.
 *
 * Immutable value object hydrated from the catalog API response.
 *
 * @since 3.0.0
 *
 * @phpstan-type FeatureAttributes array{
 *     feature_slug: string,
 *     type: string,
 *     minimum_tier: string,
 *     plugin_file: ?string,
 *     is_dot_org: bool,
 *     download_url: ?string,
 *     name: string,
 *     description: string,
 *     category: string,
 *     authors: list<string>,
 *     documentation_url: string,
 * }
 */
final class Catalog_Feature {

	/**
	 * The feature attributes.
	 *
	 * @since 3.0.0
	 *
	 * @var FeatureAttributes
	 */
	protected array $attributes = [
		'feature_slug'      => '',
		'type'              => '',
		'minimum_tier'      => '',
		'plugin_file'       => null,
		'is_dot_org'        => false,
		'download_url'      => null,
		'name'              => '',
		'description'       => '',
		'category'          => '',
		'authors'           => [],
		'documentation_url' => '',
	];

	/**
	 * Constructor for a Catalog_Feature.
	 *
	 * @since 3.0.0
	 *
	 * @phpstan-param FeatureAttributes $attributes
	 *
	 * @param array $attributes The feature attributes.
	 *
	 * @return void
	 */
	public function __construct( array $attributes ) {
		$this->attributes = $attributes;
	}

	/**
	 * Creates a Catalog_Feature from a raw data array.
	 *
	 * @since 3.0.0
	 *
	 * @param array<string, mixed> $data The feature data.
	 *
	 * @return self
	 */
	public static function from_array( array $data ): self {
		return new self(
			[
				'feature_slug'      => Cast::to_string( $data['feature_slug'] ?? '' ),
				'type'              => Cast::to_string( $data['type'] ?? '' ),
				'minimum_tier'      => Cast::to_string( $data['minimum_tier'] ?? '' ),
				'plugin_file'       => isset( $data['plugin_file'] ) ? Cast::to_string( $data['plugin_file'] ) : null,
				'is_dot_org'        => Cast::to_bool( $data['is_dot_org'] ?? false ),
				'download_url'      => isset( $data['download_url'] ) ? Cast::to_string( $data['download_url'] ) : null,
				'name'              => Cast::to_string( $data['name'] ?? '' ),
				'description'       => Cast::to_string( $data['description'] ?? '' ),
				'category'          => Cast::to_string( $data['category'] ?? '' ),
				'authors'           => isset( $data['authors'] ) && is_array( $data['authors'] )
					? array_map( [ Cast::class, 'to_string' ], array_values( $data['authors'] ) )
					: [],
				'documentation_url' => Cast::to_string( $data['documentation_url'] ?? '' ),
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
		return $this->attributes;
	}

	/**
	 * Gets the feature slug.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_feature_slug(): string {
		return $this->attributes['feature_slug'];
	}

	/**
	 * Gets the feature type (flag, plugin, or theme).
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_type(): string {
		return $this->attributes['type'];
	}

	/**
	 * Gets the minimum tier required for this feature.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_minimum_tier(): string {
		return $this->attributes['minimum_tier'];
	}

	/**
	 * Gets the plugin file path, or null if not a plugin.
	 *
	 * @since 3.0.0
	 *
	 * @return string|null
	 */
	public function get_plugin_file(): ?string {
		return $this->attributes['plugin_file'];
	}

	/**
	 * Whether the feature is available on WordPress.org.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public function is_dot_org(): bool {
		return $this->attributes['is_dot_org'];
	}

	/**
	 * Gets the download URL, or null if is_dot_org is true.
	 *
	 * @since 3.0.0
	 *
	 * @return string|null
	 */
	public function get_download_url(): ?string {
		return $this->attributes['download_url'];
	}

	/**
	 * Gets the display name.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->attributes['name'];
	}

	/**
	 * Gets the short description.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return $this->attributes['description'];
	}

	/**
	 * Gets the category for grouping/filtering.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_category(): string {
		return $this->attributes['category'];
	}

	/**
	 * Gets the author/brand names.
	 *
	 * @since 3.0.0
	 *
	 * @return string[]
	 */
	public function get_authors(): array {
		return $this->attributes['authors'];
	}

	/**
	 * Gets the documentation URL.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_documentation_url(): string {
		return $this->attributes['documentation_url'];
	}
}
