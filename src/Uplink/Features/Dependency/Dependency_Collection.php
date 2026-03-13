<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Dependency;

/**
 * Indexed collection of dependencies keyed by feature slug and version.
 *
 * @since 3.0.0
 */
final class Dependency_Collection {

	/**
	 * Fallback version key used when no exact version match is found.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const VERSION_DEFAULT = 'default';

	/**
	 * Index: feature_slug → version → Dependency[].
	 *
	 * @since 3.0.0
	 *
	 * @var array<string, array<string, Dependency[]>>
	 */
	private array $index = [];

	/**
	 * Adds a set of dependencies for a feature slug and version key.
	 *
	 * @since 3.0.0
	 *
	 * @param string       $feature_slug The feature slug.
	 * @param string       $version      The version key (e.g. "1.2.3" or "default").
	 * @param Dependency[] $dependencies The dependency list for this entry.
	 *
	 * @return void
	 */
	public function add( string $feature_slug, string $version, array $dependencies ): void {
		if ( ! isset( $this->index[ $feature_slug ] ) ) {
			$this->index[ $feature_slug ] = [];
		}

		$this->index[ $feature_slug ][ $version ] = $dependencies;
	}

	/**
	 * Returns the dependency list for a given feature slug and version.
	 *
	 * Lookup order:
	 *   1. Exact version match (e.g. "1.2.3")
	 *   2. "default" version entry
	 *   3. Empty array
	 *
	 * @since 3.0.0
	 *
	 * @param string $feature_slug    The feature slug to look up.
	 * @param string $feature_version The feature version (e.g. "1.2.3").
	 *
	 * @return Dependency[] Empty array if no dependencies are declared.
	 */
	public function get( string $feature_slug, string $feature_version ): array {
		if ( ! isset( $this->index[ $feature_slug ] ) ) {
			return [];
		}

		$versions = $this->index[ $feature_slug ];

		if ( isset( $versions[ $feature_version ] ) ) {
			return $versions[ $feature_version ];
		}

		return $versions[ self::VERSION_DEFAULT ] ?? [];
	}
}
