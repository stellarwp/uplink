<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\CLI\Commands;

use StellarWP\Uplink\Catalog\Catalog_Collection;
use StellarWP\Uplink\Catalog\Catalog_Repository;
use StellarWP\Uplink\Catalog\Results\Catalog_Feature;
use StellarWP\Uplink\Catalog\Results\Catalog_Tier;
use StellarWP\Uplink\Catalog\Results\Product_Catalog;
use StellarWP\Uplink\Catalog\Results\Tier_Collection;
use StellarWP\Uplink\CLI\Commands\Catalog as Catalog_Command;
use StellarWP\Uplink\Tests\CLI\Spy_Logger;
use StellarWP\Uplink\Tests\Traits\With_Uopz;
use StellarWP\Uplink\Tests\UplinkTestCase;
use WP_CLI;
use WP_Error;

/**
 * Tests for the WP-CLI `wp uplink catalog` command.
 *
 * @since 3.0.0
 */
final class CatalogTest extends UplinkTestCase {

	use With_Uopz;

	/** @var Spy_Logger */
	private Spy_Logger $logger;

	/** @var Catalog_Collection */
	private Catalog_Collection $catalogs;

	protected function setUp(): void {
		parent::setUp();

		if ( function_exists( 'uopz_allow_exit' ) ) {
			uopz_allow_exit( false );
		}

		$utils_file = dirname( ( new \ReflectionClass( WP_CLI::class ) )->getFileName() ) . '/utils.php';
		if ( file_exists( $utils_file ) ) {
			require_once $utils_file;
		}

		$this->logger = new Spy_Logger();
		WP_CLI::set_logger( $this->logger );

		$this->catalogs = $this->build_test_catalogs();
	}

	protected function tearDown(): void {
		if ( function_exists( 'uopz_allow_exit' ) ) {
			uopz_allow_exit( true );
		}

		parent::tearDown();
	}

	// ------------------------------------------------------------------
	// list
	// ------------------------------------------------------------------

	public function test_list_outputs_all_products_as_json(): void {
		$command = $this->make_command( $this->catalogs );

		$items = $this->run_list_json( $command );

		$this->assertCount( 2, $items );
		$this->assertSame( 'kadence', $items[0]['product_slug'] );
		$this->assertSame( 'givewp', $items[1]['product_slug'] );
	}

	public function test_list_shows_tier_and_feature_counts(): void {
		$command = $this->make_command( $this->catalogs );

		$items = $this->run_list_json( $command );

		$this->assertSame( '2', $items[0]['tiers'] );
		$this->assertSame( '2', $items[0]['features'] );
		$this->assertSame( '1', $items[1]['tiers'] );
		$this->assertSame( '1', $items[1]['features'] );
	}

	public function test_list_calls_error_on_wp_error(): void {
		$repository = $this->makeEmpty(
			Catalog_Repository::class,
			[
				'get' => new WP_Error( 'api_error', 'Could not fetch catalog.' ),
			]
		);

		$command = new Catalog_Command( $repository );
		$command->list_( [], [] );

		$this->assertSame( 'Could not fetch catalog.', $this->logger->last_error );
	}

	// ------------------------------------------------------------------
	// tiers
	// ------------------------------------------------------------------

	public function test_tiers_outputs_tiers_for_product(): void {
		$command = $this->make_command( $this->catalogs );

		$items = $this->run_tiers_json( $command, 'kadence' );

		$this->assertCount( 2, $items );
		$this->assertSame( 'starter', $items[0]['slug'] );
		$this->assertSame( 'Starter', $items[0]['name'] );
		$this->assertSame( 1, $items[0]['rank'] );
		$this->assertSame( 'pro', $items[1]['slug'] );
		$this->assertSame( 2, $items[1]['rank'] );
	}

	public function test_tiers_calls_error_for_nonexistent_product(): void {
		$command = $this->make_command( $this->catalogs );

		$command->tiers( [ 'nonexistent' ], [] );

		$this->assertSame( 'Product "nonexistent" not found in catalog.', $this->logger->last_error );
	}

	public function test_tiers_logs_no_tiers_for_empty_tier_collection(): void {
		$catalog  = new Product_Catalog( 'empty-product', new Tier_Collection(), [] );
		$catalogs = new Catalog_Collection();
		$catalogs->add( $catalog );

		$command = $this->make_command( $catalogs );
		$command->tiers( [ 'empty-product' ], [] );

		$this->assertSame( 'No tiers found.', $this->logger->last_info );
	}

	// ------------------------------------------------------------------
	// features
	// ------------------------------------------------------------------

	public function test_features_outputs_features_for_product(): void {
		$command = $this->make_command( $this->catalogs );

		$items = $this->run_features_json( $command, 'kadence' );

		$this->assertCount( 2, $items );
		$this->assertSame( 'kadence-blocks-pro', $items[0]['feature_slug'] );
		$this->assertSame( 'plugin', $items[0]['type'] );
		$this->assertSame( 'starter', $items[0]['minimum_tier'] );
	}

	public function test_features_converts_booleans_to_strings(): void {
		$command = $this->make_command( $this->catalogs );

		$items = $this->run_features_json( $command, 'kadence' );

		$this->assertSame( 'true', $items[0]['is_dot_org'] );
		$this->assertSame( 'false', $items[1]['is_dot_org'] );
	}

	public function test_features_joins_authors_array(): void {
		$command = $this->make_command( $this->catalogs );

		$items = $this->run_features_json( $command, 'kadence' );

		$this->assertSame( 'Starter Templates', $items[0]['name'] );
	}

	public function test_features_calls_error_for_nonexistent_product(): void {
		$command = $this->make_command( $this->catalogs );

		$command->features( [ 'nonexistent' ], [] );

		$this->assertSame( 'Product "nonexistent" not found in catalog.', $this->logger->last_error );
	}

	public function test_features_logs_no_features_for_empty_list(): void {
		$tiers = new Tier_Collection();
		$tiers->add(
			Catalog_Tier::from_array(
				[
					'slug' => 'starter',
					'name' => 'Starter',
					'rank' => 1,
				] 
			) 
		);
		$catalog  = new Product_Catalog( 'empty-product', $tiers, [] );
		$catalogs = new Catalog_Collection();
		$catalogs->add( $catalog );

		$command = $this->make_command( $catalogs );
		$command->features( [ 'empty-product' ], [] );

		$this->assertSame( 'No features found.', $this->logger->last_info );
	}

	// ------------------------------------------------------------------
	// refresh
	// ------------------------------------------------------------------

	public function test_refresh_calls_success_on_success(): void {
		$repository = $this->makeEmpty(
			Catalog_Repository::class,
			[
				'refresh' => $this->catalogs,
				'get'     => $this->catalogs,
			]
		);

		$command = new Catalog_Command( $repository );

		ob_start();
		$command->refresh( [], [ 'format' => 'json' ] );
		ob_end_clean();

		$this->assertSame( 'Catalog refreshed.', $this->logger->last_success );
	}

	public function test_refresh_calls_error_on_failure(): void {
		$repository = $this->makeEmpty(
			Catalog_Repository::class,
			[
				'refresh' => new WP_Error( 'api_error', 'API is down.' ),
			]
		);

		$command = new Catalog_Command( $repository );
		$command->refresh( [], [] );

		$this->assertSame( 'API is down.', $this->logger->last_error );
	}

	// ------------------------------------------------------------------
	// status
	// ------------------------------------------------------------------

	public function test_status_shows_never_fetched(): void {
		$repository = $this->makeEmpty(
			Catalog_Repository::class,
			[
				'get_last_success_at' => null,
				'get_last_failure_at' => null,
				'get_last_error'      => null,
			]
		);

		$command = new Catalog_Command( $repository );
		$command->status( [], [] );

		$this->assertSame( 'Catalog has never been fetched.', $this->logger->last_info );
	}

	public function test_status_shows_last_success_timestamp(): void {
		$timestamp = 1700000000;

		$repository = $this->makeEmpty(
			Catalog_Repository::class,
			[
				'get_last_success_at' => $timestamp,
				'get_last_failure_at' => null,
				'get_last_error'      => null,
			] 
		);

		$command = new Catalog_Command( $repository );
		$command->status( [], [] );

		$expected = sprintf( 'Last successful fetch: %s', gmdate( 'Y-m-d H:i:s', $timestamp ) );
		$this->assertContains( $expected, $this->logger->info_messages );
	}

	public function test_status_shows_last_failure_and_error(): void {
		$timestamp = 1700000000;
		$error     = new WP_Error( 'timeout', 'Connection timed out.' );

		$repository = $this->makeEmpty(
			Catalog_Repository::class,
			[
				'get_last_success_at' => null,
				'get_last_failure_at' => $timestamp,
				'get_last_error'      => $error,
			] 
		);

		$command = new Catalog_Command( $repository );
		$command->status( [], [] );

		$expected_failure = sprintf( 'Last failed fetch: %s', gmdate( 'Y-m-d H:i:s', $timestamp ) );
		$this->assertContains( $expected_failure, $this->logger->info_messages );
		$this->assertSame( 'Last error: Connection timed out. (timeout)', $this->logger->last_warning );
	}

	// ------------------------------------------------------------------
	// delete
	// ------------------------------------------------------------------

	public function test_delete_calls_success(): void {
		$repository = $this->makeEmpty(
			Catalog_Repository::class,
			[
				'delete_catalog' => null,
			] 
		);

		$command = new Catalog_Command( $repository );
		$command->delete( [], [] );

		$this->assertSame( 'Catalog cache deleted.', $this->logger->last_success );
	}

	// ------------------------------------------------------------------
	// Helpers
	// ------------------------------------------------------------------

	/**
	 * Builds a test Catalog_Collection with two products.
	 *
	 * @return Catalog_Collection
	 */
	private function build_test_catalogs(): Catalog_Collection {
		$kadence_tiers = new Tier_Collection();
		$kadence_tiers->add(
			Catalog_Tier::from_array(
				[
					'slug'         => 'starter',
					'name'         => 'Starter',
					'rank'         => 1,
					'purchase_url' => 'https://example.com/buy/starter',
				] 
			) 
		);
		$kadence_tiers->add(
			Catalog_Tier::from_array(
				[
					'slug'         => 'pro',
					'name'         => 'Pro',
					'rank'         => 2,
					'purchase_url' => 'https://example.com/buy/pro',
				] 
			) 
		);

		$kadence_features = [
			Catalog_Feature::from_array(
				[
					'feature_slug'      => 'kadence-blocks-pro',
					'type'              => 'plugin',
					'minimum_tier'      => 'starter',
					'name'              => 'Starter Templates',
					'description'       => 'Pro blocks for Kadence.',
					'category'          => 'Design',
					'plugin_file'       => 'kadence-blocks-pro/kadence-blocks-pro.php',
					'is_dot_org'        => true,
					'documentation_url' => 'https://example.com/docs',
				] 
			),
			Catalog_Feature::from_array(
				[
					'feature_slug'      => 'kadence-pro-flag',
					'type'              => 'flag',
					'minimum_tier'      => 'pro',
					'name'              => 'Pro Flag',
					'description'       => 'A pro-only flag.',
					'category'          => 'Design',
					'is_dot_org'        => false,
					'documentation_url' => 'https://example.com/docs/flag',
				] 
			),
		];

		$kadence = new Product_Catalog( 'kadence', $kadence_tiers, $kadence_features );

		$givewp_tiers = new Tier_Collection();
		$givewp_tiers->add(
			Catalog_Tier::from_array(
				[
					'slug'         => 'basic',
					'name'         => 'Basic',
					'rank'         => 1,
					'purchase_url' => 'https://example.com/buy/basic',
				] 
			) 
		);

		$givewp_features = [
			Catalog_Feature::from_array(
				[
					'feature_slug'      => 'give-recurring',
					'type'              => 'plugin',
					'minimum_tier'      => 'basic',
					'name'              => 'Recurring Donations',
					'description'       => 'Accept recurring donations.',
					'category'          => 'Fundraising',
					'plugin_file'       => 'give-recurring/give-recurring.php',
					'is_dot_org'        => false,
					'documentation_url' => 'https://example.com/docs/recurring',
				] 
			),
		];

		$givewp = new Product_Catalog( 'givewp', $givewp_tiers, $givewp_features );

		$collection = new Catalog_Collection();
		$collection->add( $kadence );
		$collection->add( $givewp );

		return $collection;
	}

	/**
	 * Creates a Catalog_Command with a mocked repository returning the given catalog.
	 *
	 * @param Catalog_Collection $catalogs
	 *
	 * @return Catalog_Command
	 */
	private function make_command( Catalog_Collection $catalogs ): Catalog_Command {
		$repository = $this->makeEmpty(
			Catalog_Repository::class,
			[
				'get' => $catalogs,
			] 
		);

		return new Catalog_Command( $repository );
	}

	/**
	 * Runs list_ with --format=json and returns decoded items.
	 *
	 * @param Catalog_Command $command
	 *
	 * @return list<array<string, mixed>>
	 */
	private function run_list_json( Catalog_Command $command ): array {
		ob_start();
		$command->list_( [], [ 'format' => 'json' ] );
		$output = ob_get_clean();

		$decoded = json_decode( (string) $output, true );

		return is_array( $decoded ) ? $decoded : [];
	}

	/**
	 * Runs tiers with --format=json and returns decoded items.
	 *
	 * @param Catalog_Command $command
	 * @param string          $product_slug
	 *
	 * @return list<array<string, mixed>>
	 */
	private function run_tiers_json( Catalog_Command $command, string $product_slug ): array {
		ob_start();
		$command->tiers( [ $product_slug ], [ 'format' => 'json' ] );
		$output = ob_get_clean();

		$decoded = json_decode( (string) $output, true );

		return is_array( $decoded ) ? $decoded : [];
	}

	/**
	 * Runs features with --format=json and returns decoded items.
	 *
	 * @param Catalog_Command $command
	 * @param string          $product_slug
	 *
	 * @return list<array<string, mixed>>
	 */
	private function run_features_json( Catalog_Command $command, string $product_slug ): array {
		ob_start();
		$command->features(
			[ $product_slug ],
			[
				'format' => 'json',
				'fields' => 'feature_slug,type,minimum_tier,name,description,category,plugin_file,is_dot_org,documentation_url',
			] 
		);
		$output = ob_get_clean();

		$decoded = json_decode( (string) $output, true );

		return is_array( $decoded ) ? $decoded : [];
	}
}
