<?php declare( strict_types=1 );

namespace StellarWP\Uplink\CLI\Commands;

use StellarWP\Uplink\Catalog\Catalog_Repository;
use StellarWP\Uplink\Catalog\Results\Catalog_Tier;
use StellarWP\Uplink\CLI\Display;
use StellarWP\Uplink\Catalog\Results\Product_Catalog;
use WP_CLI;
use WP_CLI\Formatter;
use WP_CLI_Command;

/**
 * Manage the product catalog.
 *
 * ## EXAMPLES
 *
 *     # List all products in the catalog
 *     wp uplink catalog list
 *
 *     # Show tiers for a product
 *     wp uplink catalog tiers kadence
 *
 *     # Show features for a product
 *     wp uplink catalog features kadence
 *
 *     # Force refresh from the API
 *     wp uplink catalog refresh
 *
 *     # Show catalog status
 *     wp uplink catalog status
 *
 * @since 3.0.0
 */
class Catalog extends WP_CLI_Command {

	/**
	 * The catalog repository.
	 *
	 * @since 3.0.0
	 *
	 * @var Catalog_Repository
	 */
	private Catalog_Repository $repository;

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 *
	 * @param Catalog_Repository $repository The catalog repository.
	 */
	public function __construct( Catalog_Repository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Lists all products in the catalog.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 *   - yaml
	 *   - count
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # List all products
	 *     wp uplink catalog list
	 *
	 *     # List as JSON
	 *     wp uplink catalog list --format=json
	 *
	 * @subcommand list
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Associative arguments.
	 *
	 * @return void
	 */
	public function list_( array $args, array $assoc_args ): void {
		$catalogs = $this->repository->get();

		if ( is_wp_error( $catalogs ) ) {
			WP_CLI::error( $catalogs->get_error_message() );

			return; // WP_CLI::error() exits, but PHPStan needs this for type narrowing.
		}

		$items = [];

		foreach ( $catalogs as $catalog ) {
			/** @var Product_Catalog $catalog */
			$items[] = [
				'product_slug' => $catalog->get_product_slug(),
				'tiers'        => (string) $catalog->get_tiers()->count(),
				'features'     => (string) count( $catalog->get_features() ),
			];
		}

		$formatter = new Formatter(
			$assoc_args,
			[ 'product_slug', 'tiers', 'features' ]
		);

		$formatter->display_items( $items );
	}

	/**
	 * Shows tiers for a product.
	 *
	 * ## OPTIONS
	 *
	 * <product_slug>
	 * : The product slug.
	 *
	 * [--fields=<fields>]
	 * : Comma-separated list of fields to display.
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Show tiers for a product
	 *     wp uplink catalog tiers kadence
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Associative arguments.
	 *
	 * @return void
	 */
	public function tiers( array $args, array $assoc_args ): void {
		$catalog = $this->get_product_catalog( $args[0] );

		if ( $catalog === null ) {
			return;
		}

		$items = [];

		foreach ( $catalog->get_tiers() as $tier ) {
			/** @var Catalog_Tier $tier */
			$items[] = $tier->to_array();
		}

		if ( empty( $items ) ) {
			WP_CLI::log( 'No tiers found.' );

			return;
		}

		$formatter = new Formatter(
			$assoc_args,
			[ 'slug', 'name', 'rank', 'purchase_url' ]
		);

		$formatter->display_items( $items );
	}

	/**
	 * Shows features for a product.
	 *
	 * ## OPTIONS
	 *
	 * <product_slug>
	 * : The product slug.
	 *
	 * [--fields=<fields>]
	 * : Comma-separated list of fields to display.
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 *   - yaml
	 * ---
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * * feature_slug
	 * * type
	 * * minimum_tier
	 * * name
	 * * description
	 * * category
	 * * plugin_file
	 * * is_dot_org
	 * * download_url
	 * * version
	 * * authors
	 * * documentation_url
	 *
	 * ## EXAMPLES
	 *
	 *     # Show features for a product
	 *     wp uplink catalog features kadence
	 *
	 *     # Show as JSON
	 *     wp uplink catalog features kadence --format=json
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Associative arguments.
	 *
	 * @return void
	 */
	public function features( array $args, array $assoc_args ): void {
		$catalog = $this->get_product_catalog( $args[0] );

		if ( $catalog === null ) {
			return;
		}

		$features = $catalog->get_features();

		if ( empty( $features ) ) {
			WP_CLI::log( 'No features found.' );

			return;
		}

		$items = [];

		foreach ( $features as $feature ) {
			$item = $feature->to_array();

			$item['is_dot_org'] = Display::bool( ! empty( $item['is_dot_org'] ) );

			if ( is_array( $item['authors'] ) ) {
				$item['authors'] = implode( ', ', $item['authors'] );
			} else {
				$item['authors'] = '';
			}

			$items[] = $item;
		}

		$formatter = new Formatter(
			$assoc_args,
			[ 'feature_slug', 'type', 'minimum_tier', 'name', 'category' ]
		);

		$formatter->display_items( $items );
	}

	/**
	 * Force refreshes the catalog from the API.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Output format for the resulting catalog.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 *   - yaml
	 *   - count
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Refresh the catalog
	 *     wp uplink catalog refresh
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Associative arguments.
	 *
	 * @return void
	 */
	public function refresh( array $args, array $assoc_args ): void {
		$result = $this->repository->refresh();

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );

			return; // WP_CLI::error() exits, but PHPStan needs this for type narrowing.
		}

		WP_CLI::success( 'Catalog refreshed.' );

		$this->list_( $args, $assoc_args );
	}

	/**
	 * Shows the catalog cache status.
	 *
	 * Displays when the catalog was last fetched, whether the last fetch
	 * succeeded or failed, and the error message if applicable.
	 *
	 * ## EXAMPLES
	 *
	 *     # Show catalog status
	 *     wp uplink catalog status
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Associative arguments.
	 *
	 * @return void
	 */
	public function status( array $args, array $assoc_args ): void {
		$last_success = $this->repository->get_last_success_at();
		$last_failure = $this->repository->get_last_failure_at();
		$last_error   = $this->repository->get_last_error();

		if ( $last_success === null && $last_failure === null ) {
			WP_CLI::log( 'Catalog has never been fetched.' );

			return;
		}

		if ( $last_success !== null ) {
			WP_CLI::log(
				sprintf(
					'Last successful fetch: %s',
					gmdate( 'Y-m-d H:i:s', $last_success )
				)
			);
		}

		if ( $last_failure !== null ) {
			WP_CLI::log(
				sprintf(
					'Last failed fetch: %s',
					gmdate( 'Y-m-d H:i:s', $last_failure )
				)
			);
		}

		if ( $last_error !== null ) {
			WP_CLI::warning(
				sprintf(
					'Last error: %s (%s)',
					$last_error->get_error_message(),
					$last_error->get_error_code()
				)
			);
		}
	}

	/**
	 * Deletes the stored catalog cache.
	 *
	 * The next request for the catalog will fetch fresh data from the API.
	 *
	 * ## EXAMPLES
	 *
	 *     # Delete cached catalog
	 *     wp uplink catalog delete
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Associative arguments.
	 *
	 * @return void
	 */
	public function delete( array $args, array $assoc_args ): void {
		$this->repository->delete_catalog();

		WP_CLI::success( 'Catalog cache deleted.' );
	}

	/**
	 * Fetches a single product catalog by slug, with error handling.
	 *
	 * @since 3.0.0
	 *
	 * @param string $product_slug The product slug.
	 *
	 * @return Product_Catalog|null The product catalog, or null on error (error already printed).
	 */
	private function get_product_catalog( string $product_slug ): ?Product_Catalog {
		$catalogs = $this->repository->get();

		if ( is_wp_error( $catalogs ) ) {
			WP_CLI::error( $catalogs->get_error_message() );

			return null; // WP_CLI::error() exits.
		}

		$catalog = $catalogs->get( $product_slug );

		if ( $catalog === null ) {
			WP_CLI::error( sprintf( 'Product "%s" not found in catalog.', $product_slug ) );

			return null; // WP_CLI::error() exits.
		}

		return $catalog;
	}
}
