<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Licensing;

use StellarWP\Uplink\Utils\License_Key;
use StellarWP\Uplink\Licensing\Registry\Product_Registry;
use StellarWP\Uplink\Licensing\Repositories\License_Repository;
use WP_Error;

/**
 * Orchestrates unified license key discovery and persistence.
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
	 * @var Product_Repository
	 */
	private Product_Repository $product_repository;

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 *
	 * @param License_Repository $repository         The license key repository.
	 * @param Product_Registry   $registry           The product registry.
	 * @param Product_Repository $product_repository The remote product catalog repository.
	 */
	public function __construct(
		License_Repository $repository,
		Product_Registry $registry,
		Product_Repository $product_repository
	) {
		$this->repository         = $repository;
		$this->registry           = $registry;
		$this->product_repository = $product_repository;
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
	public function get(): ?string {
		$key = $this->repository->get();

		if ( $key !== null ) {
			return $key;
		}

		return $this->discover_embedded_key();
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
	public function store( string $key, bool $network = false ): bool {
		if ( ! License_Key::is_valid_format( $key ) ) {
			return false;
		}

		return $this->repository->store( $key, $network );
	}

	/**
	 * Validate a license key against the remote licensing API and store it on success.
	 *
	 * Calls Product_Repository::get() to verify the key is recognized before
	 * persisting it, which also primes the product catalog cache as a side effect.
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

		$result = $this->product_repository->get( $key, $domain );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( ! $this->repository->store( $key, $network ) ) {
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
	public function delete( bool $network = false ): bool {
		return $this->repository->delete( $network );
	}

	/**
	 * Whether a unified license key is stored or discoverable via the registry.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public function exists(): bool {
		return $this->get() !== null;
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

		/** @var string $key */
		$key = $product->embedded_key;

		$this->repository->store( $key );

		return $key;
	}
}
