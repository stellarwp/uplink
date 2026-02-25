<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Types;

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
	 * The plugin file path relative to the plugins directory.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	protected string $plugin_file;

	/**
	 * Constructor for a Feature delivered as a standalone WordPress plugin ZIP.
	 *
	 * @since 3.0.0
	 *
	 * @param string $slug          The feature slug.
	 * @param string $group         The product group (e.g. 'LearnDash', 'TEC').
	 * @param string $tier          The feature tier (e.g. 'Tier 1', 'Tier 2').
	 * @param string $name          The feature display name.
	 * @param string $description   The feature description.
	 * @param string $plugin_file   The plugin file path (e.g. 'my-plugin/my-plugin.php').
	 * @param bool   $is_available  Whether the feature is available.
	 * @param string $documentation The URL to the feature documentation.
	 *
	 * @return void
	 */
	public function __construct( string $slug, string $group, string $tier, string $name, string $description, string $plugin_file, bool $is_available, string $documentation = '' ) {
		parent::__construct( $slug, $group, $tier, $name, $description, 'zip', $is_available, $documentation );

		$this->plugin_file = $plugin_file;
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
			$data['documentation'] ?? ''
		);
	}

	/**
	 * @inheritDoc
	 */
	public function to_array(): array {
		return [
			'slug' => $this->get_slug(),
			'group' => $this->get_group(),
			'tier' => $this->get_tier(),
			'name' => $this->get_name(),
			'description' => $this->get_description(),
			'type' => $this->get_type(),
			'plugin_file' => $this->get_plugin_file(),
			'is_available' => $this->is_available(),
			'documentation' => $this->get_documentation(),
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
		return $this->plugin_file;
	}
}
