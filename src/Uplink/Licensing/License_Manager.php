<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Licensing;

use StellarWP\Uplink\Utils\License_Key;
use StellarWP\Uplink\Licensing\Contracts\Licensing_Client;
use StellarWP\Uplink\Licensing\Registry\Product_Registry;
use StellarWP\Uplink\Licensing\Repositories\License_Repository;
use StellarWP\Uplink\Licensing\Results\Product_Entry;
use StellarWP\Uplink\Licensing\Results\Validation_Result;
use WP_Error;

/**
 * Orchestrates unified license key discovery, persistence, and product catalog fetching.
 *
 * All keys must begin with the LWSW- prefix (see License_Key::is_valid_format()).
 * store() returns false and does not write to the repository when the
 * key fails this check.
 *
 * Priority order for get():
 *   1. Stored key (License_Repository) — always wins.
 *   2. Embedded key from the product registry — used when no key is stored;
 *      the first registered product with an embedded key wins and it is
 *      auto-stored for subsequent requests.
 *
 * @since 3.0.0
 */
final class License_Manager {

	/**
	 * @since 3.0.0
	 *
	 * @var License_Repository
	 */
	private License_Repository $repository;

	/**
	 * @since 3.0.0
	 *
	 * @var Product_Registry
	 */
	private Product_Registry $registry;

	/**
	 * @since 3.0.0
	 *
	 * @var Licensing_Client
	 */
	private Licensing_Client $client;

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 *
	 * @param License_Repository $repository The license repository.
	 * @param Product_Registry   $registry   The product registry.
	 * @param Licensing_Client   $client     The licensing API client.
	 */
	public function __construct(
		License_Repository $repository,
		Product_Registry $registry,
		Licensing_Client $client
	) {
		$this->repository = $repository;
		$this->registry   = $registry;
		$this->client     = $client;
	}

	/**
	 * Get the unified license key.
	 *
	 * Checks the repository first. If no key is stored, checks the product
	 * registry for an embedded key and auto-stores the first one found.
	 *
	 * @since 3.0.0
	 *
	 * @return string|null The license key, or null if none exists.
	 */
	public function get_key(): ?string {
		$key = $this->repository->get_key();

		if ( $key !== null ) {
			return $key;
		}

		$key = $this->discover_embedded_key();

		if ( $key !== null ) {
			$this->repository->store_key( $key );
		}

		return $key;
	}

	/**
	 * Store the unified license key.
	 *
	 * Returns false without writing if the key does not begin with LWSW-.
	 *
	 * @since 3.0.0
	 *
	 * @param string $key     The license key to store.
	 * @param bool   $network Whether to store at the network level (multisite only).
	 *
	 * @return bool Whether the key was successfully stored.
	 */
	public function store_key( string $key, bool $network = false ): bool {
		if ( ! License_Key::is_valid_format( $key ) ) {
			return false;
		}

		return $this->repository->store_key( $key, $network );
	}

	/**
	 * Verify a license key is recognized by the remote API and store it.
	 *
	 * Fetches the product catalog to confirm the key exists, then persists it.
	 * Does not activate any products or consume seats.
	 *
	 * @since 3.0.0
	 *
	 * @param string $key     The license key to validate and store.
	 * @param string $domain  The site domain sent to the licensing API.
	 * @param bool   $network Whether to store at the network level (multisite only).
	 *
	 * @return Product_Entry[]|WP_Error The product list on success, WP_Error on failure.
	 */
	public function validate_and_store( string $key, string $domain, bool $network = false ) {
		if ( ! License_Key::is_valid_format( $key ) ) {
			return new WP_Error(
				Error_Code::INVALID_KEY,
				__( 'The license key format is invalid.', '%TEXTDOMAIN%' ),
				[ 'status' => 400 ]
			);
		}

		/** @var Product_Entry[]|WP_Error $result */
		$result = $this->client->get_products( $key, $domain );

		if ( is_wp_error( $result ) ) {
			$data = $result->get_error_data();

			if ( ! is_array( $data ) || empty( $data['status'] ) ) {
				$result->add_data( [ 'status' => 500 ] );
			}

			return $result;
		}

		$this->repository->set_products( Product_Collection::from_array( $result ) );

		if ( ! $this->repository->store_key( $key, $network ) ) {
			return new WP_Error(
				Error_Code::STORE_FAILED,
				__( 'The license key could not be stored.', '%TEXTDOMAIN%' ),
				[ 'status' => 500 ]
			);
		}

		return $result;
	}

	/**
	 * Validate a product on this domain using the stored license key.
	 *
	 * Calls the licensing API validate endpoint to check (and potentially
	 * consume) an activation seat for the given product. On success the
	 * product cache is cleared so the next read reflects the new state.
	 *
	 * @since 3.0.0
	 *
	 * @param string $domain       The site domain.
	 * @param string $product_slug The product to validate.
	 *
	 * @return Validation_Result|WP_Error
	 */
	public function validate_product( string $domain, string $product_slug ) {
		$key = $this->get_key();

		if ( $key === null ) {
			return new WP_Error(
				Error_Code::INVALID_KEY,
				__( 'No license key is stored.', '%TEXTDOMAIN%' ),
				[ 'status' => 422 ]
			);
		}

		$result = $this->client->validate( $key, $domain, $product_slug );

		if ( is_wp_error( $result ) ) {
			$data = $result->get_error_data();

			if ( ! is_array( $data ) || empty( $data['status'] ) ) {
				$result->add_data( [ 'status' => 500 ] );
			}

			return $result;
		}

		if ( ! $result->is_valid() ) {
			return $result->to_wp_error();
		}

		$this->fetch_and_cache( $key, $domain );

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
		return $this->repository->delete_key( $network );
	}

	/**
	 * Whether a unified license key is stored or discoverable via the registry.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public function key_exists(): bool {
		return $this->get_key() !== null;
	}

	/**
	 * Get the licensed product catalog for the stored key.
	 *
	 * Returns the cached catalog if available; otherwise fetches from the
	 * licensing API and primes the cache.
	 *
	 * @since 3.0.0
	 *
	 * @param string $domain Site domain.
	 *
	 * @return Product_Collection|WP_Error WP_Error if no key is stored or the API call fails.
	 */
	public function get_products( string $domain ) {
		$key = $this->get_key();

		if ( $key === null ) {
			return new WP_Error(
				Error_Code::INVALID_KEY,
				__( 'No license key is stored.', '%TEXTDOMAIN%' ),
				[ 'status' => 422 ]
			);
		}

		$cached = $this->repository->get_products();

		if ( $cached instanceof Product_Collection ) {
			return $cached;
		}

		return $this->fetch_and_cache( $key, $domain );
	}

	/**
	 * Flush the cached product catalog and re-fetch from the API.
	 *
	 * @since 3.0.0
	 *
	 * @param string $domain Site domain.
	 *
	 * @return Product_Collection|WP_Error WP_Error if no key is stored or the API call fails.
	 */
	public function refresh_products( string $domain ) {
		$key = $this->get_key();

		if ( $key === null ) {
			return new WP_Error(
				Error_Code::INVALID_KEY,
				__( 'No license key is stored.', '%TEXTDOMAIN%' ),
				[ 'status' => 422 ]
			);
		}

		$this->repository->delete_products();

		return $this->fetch_and_cache( $key, $domain );
	}

	/**
	 * Look up the products for a license key without storing anything.
	 *
	 * Validates the key format, calls the remote API, and returns the
	 * product collection. Never persists the key or caches results.
	 *
	 * @since 3.0.0
	 *
	 * @param string $key    The license key to look up.
	 * @param string $domain The site domain.
	 *
	 * @return Product_Collection|WP_Error
	 */
	public function lookup_products( string $key, string $domain ) {
		if ( ! License_Key::is_valid_format( $key ) ) {
			return new WP_Error(
				Error_Code::INVALID_KEY,
				__( 'The license key format is invalid.', '%TEXTDOMAIN%' ),
				[ 'status' => 400 ]
			);
		}

		$result = $this->client->get_products( $key, $domain );

		if ( is_wp_error( $result ) ) {
			$data = $result->get_error_data();

			if ( ! is_array( $data ) || empty( $data['status'] ) ) {
				$result->add_data( [ 'status' => 500 ] );
			}

			return $result;
		}

		return Product_Collection::from_array( $result );
	}

	/**
	 * Fetch the product catalog from the API and cache the result.
	 *
	 * After a successful fetch, the last active date is updated for every
	 * product that reports a valid license. This anchors the grace period
	 * to the most recent confirmed-good state.
	 *
	 * @since 3.0.0
	 *
	 * @param string $key    License key.
	 * @param string $domain Site domain.
	 *
	 * @return Product_Collection|WP_Error
	 */
	private function fetch_and_cache( string $key, string $domain ) {
		/** @var Product_Entry[]|WP_Error $result */
		$result = $this->client->get_products( $key, $domain );

		if ( is_wp_error( $result ) ) {
			$this->repository->set_products( $result );

			return $result;
		}

		$collection = Product_Collection::from_array( $result );

		$this->repository->set_products( $collection );

		$current_time = time();

		foreach ( $collection as $product ) {
			/** @var Product_Entry $product */
			if ( $product->is_valid() ) {
				$this->repository->set_last_active_date( $product->get_product_slug(), $current_time );
			}
		}

		return $collection;
	}

	/**
	 * Finds the first registered product with an embedded key, stores it,
	 * and returns it. Returns null if no product reports an embedded key.
	 *
	 * @since 3.0.0
	 *
	 * @return string|null
	 */
	private function discover_embedded_key(): ?string {
		$product = $this->registry->first_with_embedded_key();

		if ( $product === null ) {
			return null;
		}

		return $product->embedded_key ?? null;
	}
}
