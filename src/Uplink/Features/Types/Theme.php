<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Types;

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
			array_merge(
				self::base_attributes( $data ),
				[
					'stylesheet' => $data['stylesheet'] ?? '',
					'authors'    => $data['authors'] ?? [],
					'is_dot_org' => $data['is_dot_org'] ?? false,
				]
			)
		);
	}

	/**
	 * Gets the primary WordPress identifier — the theme stylesheet
	 * (directory name).
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_wp_identifier(): string {
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
}
