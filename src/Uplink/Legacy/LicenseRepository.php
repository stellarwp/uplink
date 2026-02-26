<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Legacy;

/**
 * Provides access to legacy licenses reported by all Uplink
 * instances through the cross-instance filter.
 *
 * @since 3.0.0
 */
class LicenseRepository {

	/**
	 * Get all legacy licenses reported across all Uplink instances.
	 *
	 * @since 3.0.0
	 *
	 * @return LegacyLicense[]
	 */
	public function all(): array {
		return (array) apply_filters( 'stellarwp/uplink/legacy_licenses', [] );
	}

	/**
	 * Get a legacy license by resource slug.
	 *
	 * @since 3.0.0
	 *
	 * @param string $slug
	 *
	 * @return LegacyLicense|null
	 */
	public function find( string $slug ): ?LegacyLicense {
		foreach ( $this->all() as $license ) {
			if ( $license->resource_slug === $slug ) {
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
