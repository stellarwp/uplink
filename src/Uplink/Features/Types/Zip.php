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
	 * @inheritDoc
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
	 * Gets the plugin file path relative to the plugins directory.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_plugin_file(): string {
		return Cast::to_string( $this->attributes['plugin_file'] ?? '' );
	}
}
