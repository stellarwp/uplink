<?php declare( strict_types=1 );

namespace StellarWP\Uplink;

use StellarWP\Uplink\Resources\License;
use StellarWP\Uplink\Utils\Collection;

/**
 * A keyed collection of license data gathered from all registered Uplink instances.
 *
 * Uses a cross-instance WordPress filter as the primary mechanism,
 * with a direct wp_options fallback for older instances that may
 * not have the filter registered.
 *
 * Values are License objects when gathered via the filter, or plain
 * strings when falling back to wp_options for legacy instances.
 *
 * @since 3.0.0
 *
 * @extends Collection<License|string>
 */
class License_Keys extends Collection {

	/**
	 * Builds a collection of license data for the given slugs from all Uplink instances.
	 *
	 * First attempts to gather License objects via the cross-instance filter,
	 * which uses Resource::get_license_object() and respects the full key
	 * hierarchy (network option, site option, file, filtered).
	 *
	 * For any slugs still missing a key, falls back to reading directly
	 * from wp_options using the known option prefix. These legacy license keys are
	 * returned as plain strings.
	 *
	 * @since 3.0.0
	 *
	 * @param array<string> $slugs The product slugs to gather keys for.
	 *
	 * @return self
	 */
	public static function from_slugs( array $slugs ): self {
		if ( empty( $slugs ) ) {
			return new self();
		}

		/** @var array<string, License> $licenses */
		$licenses = apply_filters( 'stellarwp/uplink/licenses', [], $slugs );

		$items = [];

		foreach ( $licenses as $slug => $license ) {
			if ( ! $license instanceof License ) {
				continue;
			}

			if ( $license->get_key() !== '' ) {
				$items[ $slug ] = $license;
			}
		}

		$missing = array_diff( $slugs, array_keys( $items ) );

		foreach ( $missing as $slug ) {
			$key = self::get_key_from_options( $slug );

			if ( $key !== '' ) {
				$items[ $slug ] = $key;
			}
		}

		return new self( $items );
	}

	/**
	 * Reads a license key directly from wp_options (and network options on multisite).
	 *
	 * This is a fallback for older Uplink instances that don't have the
	 * licenses filter registered.
	 *
	 * @since 3.0.0
	 *
	 * @param string $slug The product slug.
	 *
	 * @return string The license key, or empty string if not found.
	 */
	private static function get_key_from_options( string $slug ): string {
		$option_name = License::$key_option_prefix . $slug;

		if ( is_multisite() ) {
			$network_key = get_network_option( 0, $option_name, '' );

			if ( is_string( $network_key ) && $network_key !== '' ) {
				return $network_key;
			}
		}

		$local_key = get_option( $option_name, '' );

		if ( is_string( $local_key ) && $local_key !== '' ) {
			return $local_key;
		}

		return '';
	}
}
