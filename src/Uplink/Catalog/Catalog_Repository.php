<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Catalog;

use StellarWP\Uplink\Catalog\Clients\Catalog_Client;
use WP_Error;

/**
 * Option-backed repository for the product catalog.
 *
 * This is the public API that the rest of Uplink uses — it never
 * exposes the client directly.
 *
 * @since 3.0.0
 */
final class Catalog_Repository {

	/**
	 * Option name for the catalog state envelope.
	 *
	 * Stores an associative array with four keys:
	 *   - collection      (array|null)     Catalog_Collection::to_array() from the last
	 *                                     successful API fetch, or null if never fetched.
	 *   - last_success_at (int|null)      Unix timestamp of the last successful fetch.
	 *   - last_failure_at (int|null)      Unix timestamp of the most recent failed fetch,
	 *                                     or null if no failure has occurred.
	 *   - last_error      (WP_Error|null) Error from the most recent failed attempt, or
	 *                                     null when the last fetch succeeded.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const CATALOG_STATE_OPTION_NAME = 'stellarwp_uplink_catalog_state';

	/**
	 * State envelope key for the serialized catalog collection array.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	private const STATE_KEY_COLLECTION = 'collection';

	/**
	 * State envelope key for the last successful fetch timestamp.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	private const STATE_KEY_LAST_SUCCESS_AT = 'last_success_at';

	/**
	 * State envelope key for the last failed fetch timestamp.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	private const STATE_KEY_LAST_FAILURE_AT = 'last_failure_at';

	/**
	 * State envelope key for the last fetch error.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	private const STATE_KEY_LAST_ERROR = 'last_error';

	/**
	 * The catalog client.
	 *
	 * @since 3.0.0
	 *
	 * @var Catalog_Client
	 */
	protected Catalog_Client $client;

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 *
	 * @param Catalog_Client $client The catalog client to fetch from.
	 */
	public function __construct( Catalog_Client $client ) {
		$this->client = $client;
	}

	/**
	 * Gets the full catalog, using the stored option when available.
	 *
	 * Returns the stored collection if one exists (even if a later fetch failed).
	 * Otherwise fetches from the API, which covers the first-ever request and
	 * error-only state.
	 *
	 * @since 3.0.0
	 *
	 * @return Catalog_Collection|WP_Error
	 */
	public function get() {
		$state = $this->read_catalog_state();

		if ( is_array( $state[ self::STATE_KEY_COLLECTION ] ) ) {
			return Catalog_Collection::from_array( $state[ self::STATE_KEY_COLLECTION ] );
		}

		return $this->fetch();
	}

	/**
	 * Always fetches from the API, bypassing stored state.
	 *
	 * @since 3.0.0
	 *
	 * @return Catalog_Collection|WP_Error
	 */
	public function refresh() {
		return $this->fetch();
	}

	/**
	 * Fetches from the client and persists the result.
	 *
	 * @since 3.0.0
	 *
	 * @return Catalog_Collection|WP_Error
	 */
	protected function fetch() {
		$result = $this->client->get_catalog();
		$this->set_catalog( $result );

		return $result;
	}

	/**
	 * Persist the catalog collection or a fetch error to the state option.
	 *
	 * On success (Catalog_Collection): updates collection and last_success_at,
	 * clears last_error.
	 *
	 * On failure (WP_Error): stores last_error and last_failure_at only. The
	 * existing collection is preserved so callers can still use the last
	 * known-good catalog.
	 *
	 * @since 3.0.0
	 *
	 * @param Catalog_Collection|WP_Error $data The catalog or fetch error to store.
	 *
	 * @return void
	 */
	public function set_catalog( $data ): void {
		if ( $data instanceof Catalog_Collection ) {
			$state                                    = $this->read_catalog_state();
			$state[ self::STATE_KEY_COLLECTION ]      = $data->to_array();
			$state[ self::STATE_KEY_LAST_SUCCESS_AT ] = time();
			$state[ self::STATE_KEY_LAST_ERROR ]      = null;
			update_option( self::CATALOG_STATE_OPTION_NAME, $state, false );

			return;
		}

		if ( is_wp_error( $data ) ) {
			$state                                    = $this->read_catalog_state();
			$state[ self::STATE_KEY_LAST_ERROR ]      = $data;
			$state[ self::STATE_KEY_LAST_FAILURE_AT ] = time();
			update_option( self::CATALOG_STATE_OPTION_NAME, $state, false );
		}
	}

	/**
	 * Delete the entire catalog state option.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function delete_catalog(): void {
		delete_option( self::CATALOG_STATE_OPTION_NAME );
	}

	/**
	 * Unix timestamp of the last successful catalog fetch, or null if never fetched.
	 *
	 * @since 3.0.0
	 *
	 * @return int|null
	 */
	public function get_last_success_at(): ?int {
		$value = $this->read_catalog_state()[ self::STATE_KEY_LAST_SUCCESS_AT ];

		return is_int( $value ) ? $value : null;
	}

	/**
	 * Unix timestamp of the most recent failed catalog fetch, or null if no
	 * failure has occurred.
	 *
	 * @since 3.0.0
	 *
	 * @return int|null
	 */
	public function get_last_failure_at(): ?int {
		$value = $this->read_catalog_state()[ self::STATE_KEY_LAST_FAILURE_AT ];

		return is_int( $value ) ? $value : null;
	}

	/**
	 * WP_Error from the most recent failed fetch attempt, or null if the last
	 * fetch was successful (or no fetch has occurred).
	 *
	 * @since 3.0.0
	 *
	 * @return WP_Error|null
	 */
	public function get_last_error(): ?WP_Error {
		$error = $this->read_catalog_state()[ self::STATE_KEY_LAST_ERROR ];

		return $error instanceof WP_Error ? $error : null;
	}

	/**
	 * Read the raw catalog state array from the option, returning a zeroed
	 * default when nothing has been stored.
	 *
	 * @since 3.0.0
	 *
	 * @return array{collection: array<array<string,mixed>>|null, last_success_at: int|null, last_failure_at: int|null, last_error: WP_Error|null}
	 */
	private function read_catalog_state(): array {
		$raw = get_option( self::CATALOG_STATE_OPTION_NAME, null );

		if ( ! is_array( $raw ) ) {
			return [
				self::STATE_KEY_COLLECTION      => null,
				self::STATE_KEY_LAST_SUCCESS_AT => null,
				self::STATE_KEY_LAST_FAILURE_AT => null,
				self::STATE_KEY_LAST_ERROR      => null,
			];
		}

		$collection = null;
		if ( isset( $raw[ self::STATE_KEY_COLLECTION ] ) && is_array( $raw[ self::STATE_KEY_COLLECTION ] ) ) {
			/** @var array<array<string, mixed>> $collection */
			$collection = $raw[ self::STATE_KEY_COLLECTION ];
		}

		$last_success_at = isset( $raw[ self::STATE_KEY_LAST_SUCCESS_AT ] ) && is_int( $raw[ self::STATE_KEY_LAST_SUCCESS_AT ] ) ? $raw[ self::STATE_KEY_LAST_SUCCESS_AT ] : null;
		$last_failure_at = isset( $raw[ self::STATE_KEY_LAST_FAILURE_AT ] ) && is_int( $raw[ self::STATE_KEY_LAST_FAILURE_AT ] ) ? $raw[ self::STATE_KEY_LAST_FAILURE_AT ] : null;
		$last_error      = isset( $raw[ self::STATE_KEY_LAST_ERROR ] ) && $raw[ self::STATE_KEY_LAST_ERROR ] instanceof WP_Error ? $raw[ self::STATE_KEY_LAST_ERROR ] : null;

		return [
			self::STATE_KEY_COLLECTION      => $collection,
			self::STATE_KEY_LAST_SUCCESS_AT => $last_success_at,
			self::STATE_KEY_LAST_FAILURE_AT => $last_failure_at,
			self::STATE_KEY_LAST_ERROR      => $last_error,
		];
	}
}
