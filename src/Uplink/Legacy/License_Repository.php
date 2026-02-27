<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Legacy;

/**
 * Provides access to legacy licenses reported by all Uplink
 * instances through the cross-instance filter.
 *
 * @since 3.0.0
 */
class License_Repository {

	/**
	 * Get all legacy licenses reported across all Uplink instances.
	 *
	 * @since 3.0.0
	 *
	 * @return Legacy_License[]
	 */
	public function all(): array {
		$filtered_licenses = (array) apply_filters( 'stellarwp/uplink/legacy_licenses', [] );

		$licenses = [];

		foreach ( $filtered_licenses as $license ) {
			if ( is_array( $license ) ) {
				/** @var array<string, mixed> $license */
				$licenses[] = Legacy_License::fromData( $license );
			}
		}

		return $licenses;
	}

	/**
	 * Get a legacy license by resource slug.
	 *
	 * @since 3.0.0
	 *
	 * @param string $slug
	 *
	 * @return Legacy_License|null
	 */
	public function find( string $slug ): ?Legacy_License {
		foreach ( $this->all() as $license ) {
			if ( $license->slug === $slug ) {
				return $license;
			}
		}

		return null;
	}

	/**
	 * Whether any legacy licenses exist across all instances.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public function has_any(): bool {
		return count( $this->all() ) > 0;
	}
}
