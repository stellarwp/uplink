<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Licensing\Repositories;

use StellarWP\Uplink\Utils\Sanitize;

/**
 * Handles storage and CRUD operations for the unified license key.
 *
 * Unlike the per-product license keys managed by Resources\License,
 * this stores a single key that applies across all StellarWP products
 * and will back the v4 licensing REST endpoint.
 *
 * On multisite, get() checks the network option first and falls back
 * to the site option. Callers control the storage level explicitly
 * via the $network parameter on store() and delete().
 *
 * @since 3.0.0
 */
final class License_Repository {

	/**
	 * The option name used to store the unified license key.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const OPTION_NAME = 'stellarwp_uplink_unified_license_key';

	/**
	 * Get the stored unified license key.
	 *
	 * On multisite, the network-level key takes precedence over a
	 * site-level key. Returns null if no key exists at either level.
	 *
	 * @since 3.0.0
	 *
	 * @return ?string The license key, or null if not set.
	 */
	public function get(): ?string {
		if ( is_multisite() ) {
			/** @var string $key */
			$key = get_network_option( null, self::OPTION_NAME, '' );

			if ( ! empty( $key ) ) {
				return $key;
			}
		}

		/** @var string $key */
		$key = get_option( self::OPTION_NAME, '' );

		return empty( $key ) ? null : $key;
	}

	/**
	 * Store the unified license key.
	 *
	 * @since 3.0.0
	 *
	 * @param string $key     The license key to store.
	 * @param bool   $network Whether to store at the network level (multisite only).
	 *
	 * @return bool Whether the key was successfully stored.
	 */
	public function store( string $key, bool $network = false ): bool {
		$key = Sanitize::key( $key );

		if ( $network && is_multisite() ) {
			/** @var string $current */
			$current = get_network_option( null, self::OPTION_NAME, '' );

			// update_network_option() returns false when the value hasn't changed.
			if ( $current === $key ) {
				return true;
			}

			return (bool) update_network_option( null, self::OPTION_NAME, $key );
		}

		/** @var string $current */
		$current = get_option( self::OPTION_NAME, '' );

		// update_option() returns false when the value hasn't changed.
		if ( $current === $key ) {
			return true;
		}

		return (bool) update_option( self::OPTION_NAME, $key, false );
	}

	/**
	 * Delete the stored unified license key.
	 *
	 * @since 3.0.0
	 *
	 * @param bool $network Whether to delete from the network level (multisite only).
	 *
	 * @return bool Whether the key was successfully deleted.
	 */
	public function delete( bool $network = false ): bool {
		if ( $network && is_multisite() ) {
			return delete_network_option( null, self::OPTION_NAME );
		}

		return delete_option( self::OPTION_NAME );
	}

	/**
	 * Check whether a unified license key is stored.
	 *
	 * Follows the same precedence as get(): network-level on multisite,
	 * then site-level.
	 *
	 * @since 3.0.0
	 *
	 * @return bool Whether a license key exists.
	 */
	public function exists(): bool {
		return $this->get() !== null;
	}
}
