<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Licensing;

use StellarWP\Uplink\Utils\License_Key;
use StellarWP\Uplink\Licensing\Contracts\Licensing_Client;
use StellarWP\Uplink\Licensing\Registry\Product_Registry;
use StellarWP\Uplink\Licensing\Repositories\License_Repository;
use StellarWP\Uplink\Licensing\Results\Product_Entry;
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
	 * Validate a license key against the remote licensing API and store it on success.
	 *
	 * Fetches the product catalog for the given key to verify it is recognized,
	 * primes the cache, then persists the key.
	 *
	 * @since 3.0.0
	 *
	 * @param string $key     The license key to validate and store.
	 * @param string $domain  The site domain sent to the licensing API.
	 * @param bool   $network Whether to store at the network level (multisite only).
	 *
	 * @return true|WP_Error True on success, WP_Error on validation or storage failure.
	 */
	public function validate_and_store( string $key, string $domain, bool $network = false ) {
		if ( ! License_Key::is_valid_format( $key ) ) {
			return new WP_Error(
				Error_Code::INVALID_KEY,
				__( 'The license key format is invalid.', '%TEXTDOMAIN%' )
			);
		}

		/** @var Product_Entry[]|WP_Error $result */
		$result = $this->client->get_products( $key, $domain );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$this->repository->set_products( Product_Collection::from_array( $result ) );

		if ( ! $this->repository->store_key( $key, $network ) ) {
			return new WP_Error(
				Error_Code::STORE_FAILED,
				__( 'The license key could not be stored.', '%TEXTDOMAIN%' )
			);
		}

		return true;
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
				__( 'No license key is stored.', '%TEXTDOMAIN%' )
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
				__( 'No license key is stored.', '%TEXTDOMAIN%' )
			);
		}

		$this->repository->delete_products();

		return $this->fetch_and_cache( $key, $domain );
	}

	/**
	 * Fetch the product catalog from the API and cache the result.
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
