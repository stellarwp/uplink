<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Types;

use StellarWP\Uplink\Utils\Cast;

/**
 * A Feature delivered as a WordPress theme.
 *
 * The Theme_Strategy installs the theme via themes_api() + Theme_Upgrader,
 * and uses the stylesheet (directory name) to switch/detect the active theme.
 *
 * @since 3.0.0
 */
final class Theme extends Feature {

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
		$attributes['type'] = 'theme';

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
			[
				'slug'              => $data['slug'],
				'group'             => $data['group'],
				'tier'              => $data['tier'],
				'name'              => $data['name'],
				'description'       => $data['description'] ?? '',
				'type'              => 'theme',
				'stylesheet'        => $data['stylesheet'] ?? '',
				'is_available'      => $data['is_available'],
				'documentation_url' => $data['documentation_url'] ?? '',
				'authors'           => $data['authors'] ?? [],
			]
		);
	}

	/**
	 * Gets the theme stylesheet (directory name), the primary WP identifier.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_stylesheet(): string {
		return Cast::to_string( $this->attributes['stylesheet'] ?? '' );
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
}
