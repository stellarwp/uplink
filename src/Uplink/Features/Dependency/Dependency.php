<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Dependency;

use StellarWP\Uplink\Utils\Cast;

/**
 * Immutable value object representing a single feature dependency.
 *
 * A dependency may be an internal Uplink feature (plugin, theme, or flag type
 * with is_external = false) or an external third-party WordPress plugin or
 * theme (is_external = true).
 *
 * The constraint field follows Composer-style version operators:
 *   =, !=, >, <, >=, <=, ^, ~, * (any), or a bare version string like "1.2.3".
 *
 * @since 3.0.0
 */
class Dependency {

	/**
	 * Dependency delivered as a WordPress plugin.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const TYPE_PLUGIN = 'plugin';

	/**
	 * Dependency delivered as a WordPress theme.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const TYPE_THEME = 'theme';

	/**
	 * Dependency on a feature gated by a DB option flag.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const TYPE_FLAG = 'flag';

	/**
	 * The dependency type: 'plugin', 'theme', or 'flag'.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	private string $type;

	/**
	 * The feature slug of the dependency.
	 *
	 * For internal Uplink features this matches their catalog slug.
	 * For external plugins/themes this is a human-readable identifier
	 * (use plugin_file for the actual plugin path).
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	private string $feature_slug;

	/**
	 * The human-readable display name.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	private string $name;

	/**
	 * The plugin file path relative to the plugins directory.
	 *
	 * Only meaningful when type = TYPE_PLUGIN. Empty string otherwise.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	private string $plugin_file;

	/**
	 * Whether the dependency is hosted on WordPress.org.
	 *
	 * @since 3.0.0
	 *
	 * @var bool
	 */
	private bool $is_dot_org;

	/**
	 * The version constraint.
	 *
	 * Supports Composer-style operators: =, !=, >, <, >=, <=, ^, ~, *,
	 * or a bare version string like "1.2.3".
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	private string $constraint;

	/**
	 * Whether this is an external (third-party) dependency.
	 *
	 * True for dependencies outside the Uplink catalog (e.g. WooCommerce).
	 * False for dependencies that are themselves Uplink-managed features.
	 *
	 * @since 3.0.0
	 *
	 * @var bool
	 */
	private bool $is_external;

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 *
	 * @param string $type         The dependency type (use TYPE_* constants).
	 * @param string $feature_slug The dependency feature slug.
	 * @param string $name         The human-readable display name.
	 * @param string $plugin_file  Plugin file path (plugin type only).
	 * @param bool   $is_dot_org   Whether hosted on WordPress.org.
	 * @param string $constraint   The version constraint.
	 * @param bool   $is_external  Whether this is an external dependency.
	 */
	public function __construct(
		string $type,
		string $feature_slug,
		string $name,
		string $plugin_file,
		bool $is_dot_org,
		string $constraint,
		bool $is_external
	) {
		$this->type         = $type;
		$this->feature_slug = $feature_slug;
		$this->name         = $name;
		$this->plugin_file  = $plugin_file;
		$this->is_dot_org   = $is_dot_org;
		$this->constraint   = $constraint;
		$this->is_external  = $is_external;
	}

	/**
	 * Creates a Dependency from an associative array.
	 *
	 * @since 3.0.0
	 *
	 * @param array<string, mixed> $data Raw dependency data from the JSON fixture.
	 *
	 * @return self
	 */
	public static function from_array( array $data ): self {
		return new self(
			Cast::to_string( $data['type'] ?? '' ),
			Cast::to_string( $data['feature_slug'] ?? '' ),
			Cast::to_string( $data['name'] ?? '' ),
			Cast::to_string( $data['plugin_file'] ?? '' ),
			Cast::to_bool( $data['is_dot_org'] ?? false ),
			Cast::to_string( $data['constraint'] ?? '' ),
			Cast::to_bool( $data['is_external'] ?? false )
		);
	}

	/**
	 * Gets the dependency type.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_type(): string {
		return $this->type;
	}

	/**
	 * Gets the dependency feature slug.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_feature_slug(): string {
		return $this->feature_slug;
	}

	/**
	 * Gets the display name.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Gets the plugin file path.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_plugin_file(): string {
		return $this->plugin_file;
	}

	/**
	 * Whether the dependency is hosted on WordPress.org.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public function is_dot_org(): bool {
		return $this->is_dot_org;
	}

	/**
	 * Gets the version constraint.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_constraint(): string {
		return $this->constraint;
	}

	/**
	 * Whether this is an external (third-party) dependency.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public function is_external(): bool {
		return $this->is_external;
	}

	/**
	 * Converts the dependency to an associative array.
	 *
	 * @since 3.0.0
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return [
			'type'         => $this->type,
			'feature_slug' => $this->feature_slug,
			'name'         => $this->name,
			'plugin_file'  => $this->plugin_file,
			'is_dot_org'   => $this->is_dot_org,
			'constraint'   => $this->constraint,
			'is_external'  => $this->is_external,
		];
	}
}
