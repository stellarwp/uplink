<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Licensing\Repositories;

use StellarWP\Uplink\Licensing\Product_Collection;
use StellarWP\Uplink\Licensing\Results\Product_Entry;
use StellarWP\Uplink\Utils\Sanitize;
use WP_Error;

/**
 * Handles all persistence for the unified licensing subsystem.
 *
 * Covers two storage layers:
 *   - WordPress options: the unified license key (single key per site).
 *   - WordPress transients: the product catalog cache (keyed by a fixed name).
 *
 * This class is a pure data-access layer — it only reads from and writes to
 * WordPress storage. It never calls external APIs or applies business logic.
 * Use License_Manager for orchestrated fetching and key discovery.
 *
 * On multisite, get_key() checks the network option first and falls back
 * to the site option. Callers control the storage level explicitly
 * via the $network parameter on store_key() and delete_key().
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
	public const KEY_OPTION_NAME = 'stellarwp_uplink_unified_license_key';

	/**
	 * Transient key for the cached product catalog.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const PRODUCTS_TRANSIENT_KEY = 'stellarwp_uplink_licensing_products';

	/**
	 * Default cache duration in seconds (12 hours).
	 *
	 * @since 3.0.0
	 *
	 * @var int
	 */
	public const CACHE_DURATION = HOUR_IN_SECONDS * 12;

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
	public function get_key(): ?string {
		if ( is_multisite() ) {
			/** @var string $key */
			$key = get_network_option( null, self::KEY_OPTION_NAME, '' );

			if ( ! empty( $key ) ) {
				return $key;
			}
		}

		/** @var string $key */
		$key = get_option( self::KEY_OPTION_NAME, '' );

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
	public function store_key( string $key, bool $network = false ): bool {
		$key = Sanitize::key( $key );

		if ( $network && is_multisite() ) {
			/** @var string $current */
			$current = get_network_option( null, self::KEY_OPTION_NAME, '' );

			// update_network_option() returns false when the value hasn't changed.
			if ( $current === $key ) {
				return true;
			}

			$result = (bool) update_network_option( null, self::KEY_OPTION_NAME, $key );

			if ( $result ) {
				/**
				 * Fires when the unified license key is changed.
				 *
				 * @since 3.0.0
				 *
				 * @param string $new_key The new license key.
				 * @param string $old_key The previous license key.
				 */
				do_action( 'stellarwp/uplink/unified_license_key_changed', $key, $current );
			}

			return $result;
		}

		/** @var string $current */
		$current = get_option( self::KEY_OPTION_NAME, '' );

		// update_option() returns false when the value hasn't changed.
		if ( $current === $key ) {
			return true;
		}

		$result = (bool) update_option( self::KEY_OPTION_NAME, $key, false );

		if ( $result ) {
			/**
			 * Fires when the unified license key is changed.
			 *
			 * @since 3.0.0
			 *
			 * @param string $new_key The new license key.
			 * @param string $old_key The previous license key.
			 */
			do_action( 'stellarwp/uplink/unified_license_key_changed', $key, $current );
		}

		return $result;
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
	public function delete_key( bool $network = false ): bool {
		$old_key = $this->get_key() ?? '';

		if ( $network && is_multisite() ) {
			$result = delete_network_option( null, self::KEY_OPTION_NAME );
		} else {
			$result = delete_option( self::KEY_OPTION_NAME );
		}

		if ( $result && $old_key !== '' ) {
			/**
			 * Fires when the unified license key is changed.
			 *
			 * @since 3.0.0
			 *
			 * @param string $new_key The new license key.
			 * @param string $old_key The previous license key.
			 */
			do_action( 'stellarwp/uplink/unified_license_key_changed', '', $old_key );
		}

		return $result;
	}

	/**
	 * Check whether a unified license key is stored.
	 *
	 * Follows the same precedence as get_key(): network-level on multisite,
	 * then site-level.
	 *
	 * @since 3.0.0
	 *
	 * @return bool Whether a license key exists.
	 */
	public function key_exists(): bool {
		return $this->get_key() !== null;
	}

	/**
	 * Read the cached product catalog from the transient.
	 *
	 * Returns null when no catalog has been cached yet. Use
	 * License_Manager::get_products() for a call that will fetch from the
	 * API on a cache miss.
	 *
	 * @since 3.0.0
	 *
	 * @return Product_Collection|WP_Error|null Cached value, WP_Error on error, or null on miss.
	 */
	public function get_products() {
		$products = get_transient( self::PRODUCTS_TRANSIENT_KEY );

		if ( is_wp_error( $products ) ) {
			return $products;
		}

		if ( is_array( $products ) ) {
			/** @var array<int, array<string, mixed>> $products */
			return Product_Collection::from_array( $products );
		}

		return null;
	}

	/**
	 * Write the product catalog to the transient cache.
	 *
	 * @since 3.0.0
	 *
	 * @param Product_Collection|WP_Error $data The catalog data to cache.
	 *
	 * @return void
	 */
	public function set_products( $data ): void {
		if ( $data instanceof Product_Collection ) {
			set_transient( self::PRODUCTS_TRANSIENT_KEY, $data->to_array(), self::CACHE_DURATION );

			return;
		}

		if ( is_wp_error( $data ) ) {
			set_transient( self::PRODUCTS_TRANSIENT_KEY, $data, self::CACHE_DURATION );
		}
	}

	/**
	 * Delete the cached product catalog.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function delete_products(): void {
		delete_transient( self::PRODUCTS_TRANSIENT_KEY );
	}

	/**
	 * Get a specific product entry from the cached catalog.
	 *
	 * Returns null if no catalog is cached or the product is not found.
	 *
	 * @since 3.0.0
	 *
	 * @param string $slug Product slug.
	 *
	 * @return Product_Entry|null
	 */
	public function get_product( string $slug ): ?Product_Entry {
		$products = $this->get_products();

		if ( ! $products instanceof Product_Collection ) {
			return null;
		}

		return $products->get( $slug );
	}

	/**
	 * Whether a product exists in the cached catalog.
	 *
	 * @since 3.0.0
	 *
	 * @param string $slug Product slug.
	 *
	 * @return bool
	 */
	public function has_product( string $slug ): bool {
		return $this->get_product( $slug ) !== null;
	}

	/**
	 * Whether a product has a valid license status in the cached catalog.
	 *
	 * @since 3.0.0
	 *
	 * @param string $slug Product slug.
	 *
	 * @return bool
	 */
	public function is_product_valid( string $slug ): bool {
		$product = $this->get_product( $slug );

		return $product !== null && $product->is_valid();
	}
}
