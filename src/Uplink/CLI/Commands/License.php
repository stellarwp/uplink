<?php declare( strict_types=1 );

namespace StellarWP\Uplink\CLI\Commands;

use StellarWP\Uplink\Legacy\Legacy_License;
use StellarWP\Uplink\Legacy\License_Repository as Legacy_License_Repository;
use StellarWP\Uplink\Licensing\License_Manager;
use StellarWP\Uplink\Licensing\Product_Collection;
use StellarWP\Uplink\Licensing\Results\Product_Entry;
use StellarWP\Uplink\Site\Data;
use StellarWP\Uplink\CLI\Display;
use StellarWP\Uplink\Utils\License_Key;
use WP_CLI;
use WP_CLI\Formatter;
use WP_CLI_Command;

/**
 * Manage the unified license key.
 *
 * ## EXAMPLES
 *
 *     # Show the current license key and products
 *     wp uplink license get
 *
 *     # Store a license key
 *     wp uplink license set LWSW-abcdef-123456
 *
 *     # Look up products for a key without storing
 *     wp uplink license lookup LWSW-abcdef-123456
 *
 *     # Validate a product on this domain
 *     wp uplink license validate kadence
 *
 *     # Delete the stored key
 *     wp uplink license delete
 *
 *     # Show legacy per-plugin licenses
 *     wp uplink license legacy
 *
 * @since 3.0.0
 */
class License extends WP_CLI_Command {

	/**
	 * Default fields shown in product table output.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	private const DEFAULT_PRODUCT_FIELDS = 'product_slug,tier,status,expires,site_limit,active_count';

	/**
	 * Default fields shown in legacy license table output.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	private const DEFAULT_LEGACY_FIELDS = 'slug,name,brand,key,status,expires_at';

	/**
	 * The license manager instance.
	 *
	 * @since 3.0.0
	 *
	 * @var License_Manager
	 */
	private License_Manager $manager;

	/**
	 * The site data provider.
	 *
	 * @since 3.0.0
	 *
	 * @var Data
	 */
	private Data $site_data;

	/**
	 * The legacy license repository.
	 *
	 * @since 3.0.0
	 *
	 * @var Legacy_License_Repository
	 */
	private Legacy_License_Repository $legacy_repository;

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 *
	 * @param License_Manager           $manager            The license manager.
	 * @param Data                      $site_data          The site data provider.
	 * @param Legacy_License_Repository $legacy_repository  The legacy license repository.
	 */
	public function __construct( License_Manager $manager, Data $site_data, Legacy_License_Repository $legacy_repository ) {
		$this->manager           = $manager;
		$this->site_data         = $site_data;
		$this->legacy_repository = $legacy_repository;
	}

	/**
	 * Shows the current license key and associated products.
	 *
	 * ## OPTIONS
	 *
	 * [--fields=<fields>]
	 * : Comma-separated list of product fields to display.
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
	 *     # Show current license
	 *     wp uplink license get
	 *
	 *     # Show as JSON
	 *     wp uplink license get --format=json
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Associative arguments.
	 *
	 * @return void
	 */
	public function get( array $args, array $assoc_args ): void {
		$key = $this->manager->get_key();

		if ( $key === null ) {
			WP_CLI::warning( 'No license key is stored.' );

			return;
		}

		WP_CLI::log( sprintf( 'Key: %s', $key ) );

		$products = $this->manager->get_products( $this->site_data->get_domain() );

		if ( is_wp_error( $products ) ) {
			WP_CLI::warning( $products->get_error_message() );

			return;
		}

		$this->display_products( $products, $assoc_args );
	}

	/**
	 * Validates and stores a license key.
	 *
	 * Verifies the key is recognized by the licensing API, then persists it.
	 * Does not activate any product or consume a seat.
	 *
	 * ## OPTIONS
	 *
	 * <key>
	 * : The license key to store (must start with LWSW-).
	 *
	 * [--network]
	 * : Store at the network level (multisite only).
	 *
	 * [--fields=<fields>]
	 * : Comma-separated list of product fields to display.
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
	 *     # Store a license key
	 *     wp uplink license set LWSW-abcdef-123456
	 *
	 *     # Store at network level
	 *     wp uplink license set LWSW-abcdef-123456 --network
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Associative arguments.
	 *
	 * @return void
	 */
	public function set( array $args, array $assoc_args ): void {
		$key     = $args[0];
		$network = isset( $assoc_args['network'] );
		$domain  = $this->site_data->get_domain();

		if ( ! License_Key::is_valid_format( $key ) ) {
			WP_CLI::error( 'Invalid license key format. Keys must start with LWSW-.' );

			return; // WP_CLI::error() exits, but PHPStan needs this for type narrowing.
		}

		$result = $this->manager->validate_and_store( $key, $domain, $network );

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );

			return; // WP_CLI::error() exits, but PHPStan needs this for type narrowing.
		}

		WP_CLI::success( 'License key stored.' );

		$products = $this->manager->get_products( $domain );

		if ( is_wp_error( $products ) ) {
			return;
		}

		$this->display_products( $products, $assoc_args );
	}

	/**
	 * Looks up products for a license key without storing it.
	 *
	 * ## OPTIONS
	 *
	 * <key>
	 * : The license key to look up (must start with LWSW-).
	 *
	 * [--fields=<fields>]
	 * : Comma-separated list of product fields to display.
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
	 *     # Look up a key
	 *     wp uplink license lookup LWSW-abcdef-123456
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Associative arguments.
	 *
	 * @return void
	 */
	public function lookup( array $args, array $assoc_args ): void {
		$key    = $args[0];
		$domain = $this->site_data->get_domain();

		$result = $this->manager->lookup_products( $key, $domain );

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );

			return; // WP_CLI::error() exits, but PHPStan needs this for type narrowing.
		}

		$this->display_products( $result, $assoc_args );
	}

	/**
	 * Validates a product on this domain using the stored license key.
	 *
	 * This may consume an activation seat.
	 *
	 * ## OPTIONS
	 *
	 * <product_slug>
	 * : The product slug to validate.
	 *
	 * ## EXAMPLES
	 *
	 *     # Validate a product
	 *     wp uplink license validate kadence
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Associative arguments.
	 *
	 * @return void
	 */
	public function validate( array $args, array $assoc_args ): void {
		$product_slug = $args[0];
		$domain       = $this->site_data->get_domain();

		$result = $this->manager->validate_product( $domain, $product_slug );

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );

			return; // WP_CLI::error() exits, but PHPStan needs this for type narrowing.
		}

		WP_CLI::success( sprintf( 'Product "%s" validated successfully.', $product_slug ) );
	}

	/**
	 * Deletes the stored unified license key.
	 *
	 * This only removes the locally stored key. It does not free any
	 * activation seats on the licensing service.
	 *
	 * ## OPTIONS
	 *
	 * [--network]
	 * : Delete from the network level (multisite only).
	 *
	 * ## EXAMPLES
	 *
	 *     # Delete the stored key
	 *     wp uplink license delete
	 *
	 *     # Delete network-level key
	 *     wp uplink license delete --network
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Associative arguments.
	 *
	 * @return void
	 */
	public function delete( array $args, array $assoc_args ): void {
		$network = isset( $assoc_args['network'] );

		$this->manager->delete_key( $network );

		WP_CLI::success( 'License key deleted.' );
	}

	/**
	 * Lists legacy per-plugin licenses discovered across all Uplink instances.
	 *
	 * These are old-style license keys stored individually by each plugin,
	 * before the unified LWSW- key was adopted. This is a read-only view.
	 *
	 * ## OPTIONS
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
	 *     # List legacy licenses
	 *     wp uplink license legacy
	 *
	 *     # Show as JSON
	 *     wp uplink license legacy --format=json
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Associative arguments.
	 *
	 * @return void
	 */
	public function legacy( array $args, array $assoc_args ): void {
		$licenses = $this->legacy_repository->all();

		if ( count( $licenses ) === 0 ) {
			WP_CLI::log( 'No legacy licenses found.' );

			return;
		}

		$items = array_map(
			static fn( Legacy_License $license ): array => $license->to_array(),
			$licenses
		);

		$formatter = new Formatter(
			$assoc_args,
			explode( ',', self::DEFAULT_LEGACY_FIELDS )
		);

		$formatter->display_items( $items );
	}

	/**
	 * Displays a product collection as a table.
	 *
	 * @since 3.0.0
	 *
	 * @param Product_Collection    $products   The product collection.
	 * @param array<string, string> $assoc_args Associative arguments for the formatter.
	 *
	 * @return void
	 */
	private function display_products( Product_Collection $products, array $assoc_args ): void {
		if ( $products->count() === 0 ) {
			WP_CLI::log( 'No products found.' );

			return;
		}

		$items = [];

		foreach ( $products as $product ) {
			$items[] = $this->product_to_display_item( $product );
		}

		$formatter = new Formatter(
			$assoc_args,
			explode(
				',',
				self::DEFAULT_PRODUCT_FIELDS
			)
		);

		$formatter->display_items( $items );
	}

	/**
	 * Converts a product entry to a flat display-ready associative array.
	 *
	 * @since 3.0.0
	 *
	 * @param Product_Entry $product The product entry.
	 *
	 * @return array<string, mixed>
	 */
	private function product_to_display_item( Product_Entry $product ): array {
		$site_limit = $product->get_site_limit();

		return [
			'product_slug'      => $product->get_product_slug(),
			'tier'              => $product->get_tier(),
			'pending_tier'      => $product->get_pending_tier() ?? '',
			'status'            => $product->get_status(),
			'expires'           => $product->get_expires()->format( 'Y-m-d H:i:s' ),
			'site_limit'        => $site_limit === 0 ? 'unlimited' : (string) $site_limit,
			'active_count'      => (string) $product->get_active_count(),
			'over_limit'        => Display::bool( $product->is_over_limit() ),
			'installed_here'    => Display::nullable_bool( $product->get_installed_here() ),
			'validation_status' => $product->get_validation_status() ?? '',
			'is_valid'          => Display::bool( $product->is_valid() ),
		];
	}
}
