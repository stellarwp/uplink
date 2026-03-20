<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\CLI\Commands;

use StellarWP\Uplink\CLI\Commands\License as License_Command;
use StellarWP\Uplink\Legacy\License_Repository as Legacy_License_Repository;
use StellarWP\Uplink\Licensing\License_Manager;
use StellarWP\Uplink\Licensing\Product_Collection;
use StellarWP\Uplink\Licensing\Results\Product_Entry;
use StellarWP\Uplink\Site\Data;
use StellarWP\Uplink\Tests\CLI\Spy_Logger;
use StellarWP\Uplink\Tests\Traits\With_Uopz;
use StellarWP\Uplink\Tests\UplinkTestCase;
use WP_CLI;
use WP_Error;

/**
 * Tests for the WP-CLI `wp uplink license` command.
 *
 * @since 3.0.0
 */
final class LicenseTest extends UplinkTestCase {

	use With_Uopz;

	/** @var Spy_Logger */
	private Spy_Logger $logger;

	/** @var Data */
	private Data $site_data;

	/** @var Product_Collection */
	private Product_Collection $products;

	/** @var Legacy_License_Repository */
	private Legacy_License_Repository $legacy_repository;

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

		$this->site_data = $this->makeEmpty(
			Data::class,
			[
				'get_domain' => 'example.com',
			]
		);

		$this->legacy_repository = new Legacy_License_Repository();

		$this->products = new Product_Collection();
		$this->products->add(
			Product_Entry::from_array(
				[
					'product_slug' => 'kadence',
					'tier'         => 'starter',
					'status'       => 'active',
					'expires'      => '2027-01-01 00:00:00',
					'activations'  => [
						'site_limit'   => 5,
						'active_count' => 2,
					],
				]
			)
		);
		$this->products->add(
			Product_Entry::from_array(
				[
					'product_slug' => 'givewp',
					'tier'         => 'pro',
					'status'       => 'active',
					'expires'      => '2027-06-15 00:00:00',
					'activations'  => [
						'site_limit'   => 0,
						'active_count' => 10,
					],
				]
			)
		);
	}

	protected function tearDown(): void {
		if ( function_exists( 'uopz_allow_exit' ) ) {
			uopz_allow_exit( true );
		}

		parent::tearDown();
	}

	// ------------------------------------------------------------------
	// get
	// ------------------------------------------------------------------

	public function test_get_shows_key_and_products(): void {
		$manager = $this->makeEmpty(
			License_Manager::class,
			[
				'get_key'      => 'LWSW-test-key-123',
				'get_products' => $this->products,
			]
		);

		$command = new License_Command( $manager, $this->site_data, $this->legacy_repository );

		$items = $this->run_get_json( $command );

		$this->assertStringContainsString( 'LWSW-test-key-123', $this->logger->last_info );
		$this->assertCount( 2, $items );
		$this->assertSame( 'kadence', $items[0]['product_slug'] );
		$this->assertSame( 'givewp', $items[1]['product_slug'] );
	}

	public function test_get_warns_when_no_key_stored(): void {
		$manager = $this->makeEmpty(
			License_Manager::class,
			[
				'get_key' => null,
			]
		);

		$command = new License_Command( $manager, $this->site_data, $this->legacy_repository );
		$command->get( [], [] );

		$this->assertSame( 'No license key is stored.', $this->logger->last_warning );
	}

	public function test_get_warns_when_products_return_error(): void {
		$manager = $this->makeEmpty(
			License_Manager::class,
			[
				'get_key'      => 'LWSW-test-key-123',
				'get_products' => new WP_Error( 'api_error', 'Could not fetch products.' ),
			]
		);

		$command = new License_Command( $manager, $this->site_data, $this->legacy_repository );
		$command->get( [], [] );

		$this->assertSame( 'Could not fetch products.', $this->logger->last_warning );
	}

	public function test_get_shows_product_fields(): void {
		$manager = $this->makeEmpty(
			License_Manager::class,
			[
				'get_key'      => 'LWSW-test-key-123',
				'get_products' => $this->products,
			]
		);

		$command = new License_Command( $manager, $this->site_data, $this->legacy_repository );

		$items = $this->run_get_json( $command );

		$this->assertSame( 'starter', $items[0]['tier'] );
		$this->assertSame( 'active', $items[0]['status'] );
		$this->assertSame( '5', $items[0]['site_limit'] );
		$this->assertSame( '2', $items[0]['active_count'] );
	}

	public function test_get_shows_unlimited_for_zero_site_limit(): void {
		$manager = $this->makeEmpty(
			License_Manager::class,
			[
				'get_key'      => 'LWSW-test-key-123',
				'get_products' => $this->products,
			]
		);

		$command = new License_Command( $manager, $this->site_data, $this->legacy_repository );

		$items = $this->run_get_json( $command );

		$this->assertSame( 'unlimited', $items[1]['site_limit'] );
	}

	// ------------------------------------------------------------------
	// set
	// ------------------------------------------------------------------

	public function test_set_stores_key_on_success(): void {
		$manager = $this->makeEmpty(
			License_Manager::class,
			[
				'validate_and_store' => [],
				'get_products'       => $this->products,
				'get_key'            => 'LWSW-test-key-123',
			]
		);

		$command = new License_Command( $manager, $this->site_data, $this->legacy_repository );
		$command->set( [ 'LWSW-test-key-123' ], [] );

		$this->assertSame( 'License key stored.', $this->logger->last_success );
	}

	public function test_set_calls_error_on_invalid_format(): void {
		$manager = $this->makeEmpty( License_Manager::class );
		$command = new License_Command( $manager, $this->site_data, $this->legacy_repository );

		$command->set( [ 'INVALID-KEY' ], [] );

		$this->assertSame( 'Invalid license key format. Keys must start with LWSW-.', $this->logger->last_error );
	}

	public function test_set_calls_error_on_api_failure(): void {
		$manager = $this->makeEmpty(
			License_Manager::class,
			[
				'validate_and_store' => new WP_Error( 'api_error', 'License not recognized.' ),
			]
		);

		$command = new License_Command( $manager, $this->site_data, $this->legacy_repository );
		$command->set( [ 'LWSW-bad-key' ], [] );

		$this->assertSame( 'License not recognized.', $this->logger->last_error );
	}

	// ------------------------------------------------------------------
	// lookup
	// ------------------------------------------------------------------

	public function test_lookup_displays_products_for_key(): void {
		$manager = $this->makeEmpty(
			License_Manager::class,
			[
				'lookup_products' => $this->products,
			]
		);

		$command = new License_Command( $manager, $this->site_data, $this->legacy_repository );

		$items = $this->run_lookup_json( $command, 'LWSW-test-key-123' );

		$this->assertCount( 2, $items );
		$this->assertSame( 'kadence', $items[0]['product_slug'] );
	}

	public function test_lookup_calls_error_on_failure(): void {
		$manager = $this->makeEmpty(
			License_Manager::class,
			[
				'lookup_products' => new WP_Error( 'invalid_key', 'Invalid key format.' ),
			]
		);

		$command = new License_Command( $manager, $this->site_data, $this->legacy_repository );
		$command->lookup( [ 'bad-key' ], [] );

		$this->assertSame( 'Invalid key format.', $this->logger->last_error );
	}

	// ------------------------------------------------------------------
	// validate
	// ------------------------------------------------------------------

	public function test_validate_calls_success_on_valid_product(): void {
		$manager = $this->makeEmpty(
			License_Manager::class,
			[
				'validate_product' => $this->makeEmpty(
					\StellarWP\Uplink\Licensing\Results\Validation_Result::class,
					[ 'is_valid' => true ]
				),
			]
		);

		$command = new License_Command( $manager, $this->site_data, $this->legacy_repository );
		$command->validate( [ 'kadence' ], [] );

		$this->assertSame( 'Product "kadence" validated successfully.', $this->logger->last_success );
	}

	public function test_validate_calls_error_on_failure(): void {
		$manager = $this->makeEmpty(
			License_Manager::class,
			[
				'validate_product' => new WP_Error( 'validation_failed', 'Product validation failed.' ),
			]
		);

		$command = new License_Command( $manager, $this->site_data, $this->legacy_repository );
		$command->validate( [ 'kadence' ], [] );

		$this->assertSame( 'Product validation failed.', $this->logger->last_error );
	}

	// ------------------------------------------------------------------
	// delete
	// ------------------------------------------------------------------

	public function test_delete_calls_success(): void {
		$manager = $this->makeEmpty(
			License_Manager::class,
			[
				'delete_key' => true,
			]
		);

		$command = new License_Command( $manager, $this->site_data, $this->legacy_repository );
		$command->delete( [], [] );

		$this->assertSame( 'License key deleted.', $this->logger->last_success );
	}

	// ------------------------------------------------------------------
	// display helpers
	// ------------------------------------------------------------------

	public function test_product_display_shows_boolean_fields(): void {
		$products = new Product_Collection();
		$products->add(
			Product_Entry::from_array(
				[
					'product_slug'      => 'kadence',
					'tier'              => 'starter',
					'status'            => 'active',
					'expires'           => '2027-01-01 00:00:00',
					'installed_here'    => true,
					'validation_status' => 'valid',
					'activations'       => [
						'site_limit'   => 1,
						'active_count' => 2,
					],
				]
			)
		);

		$manager = $this->makeEmpty(
			License_Manager::class,
			[
				'get_key'      => 'LWSW-test-key-123',
				'get_products' => $products,
			]
		);

		$command = new License_Command( $manager, $this->site_data, $this->legacy_repository );

		$items = $this->run_get_json( $command );

		$this->assertSame( 'true', $items[0]['installed_here'] );
		$this->assertSame( 'true', $items[0]['is_valid'] );
		$this->assertSame( 'true', $items[0]['over_limit'] );
	}

	public function test_product_display_shows_empty_for_null_optional_fields(): void {
		$products = new Product_Collection();
		$products->add(
			Product_Entry::from_array(
				[
					'product_slug' => 'kadence',
					'tier'         => 'starter',
					'status'       => 'active',
					'expires'      => '2027-01-01 00:00:00',
					'activations'  => [
						'site_limit'   => 5,
						'active_count' => 2,
					],
				]
			)
		);

		$manager = $this->makeEmpty(
			License_Manager::class,
			[
				'get_key'      => 'LWSW-test-key-123',
				'get_products' => $products,
			]
		);

		$command = new License_Command( $manager, $this->site_data, $this->legacy_repository );

		$items = $this->run_get_json( $command );

		$this->assertSame( '', $items[0]['installed_here'] );
		$this->assertSame( '', $items[0]['validation_status'] );
		$this->assertSame( '', $items[0]['pending_tier'] );
	}

	public function test_get_logs_no_products_for_empty_collection(): void {
		$manager = $this->makeEmpty(
			License_Manager::class,
			[
				'get_key'      => 'LWSW-test-key-123',
				'get_products' => new Product_Collection(),
			]
		);

		$command = new License_Command( $manager, $this->site_data, $this->legacy_repository );
		$command->get( [], [] );

		$this->assertSame( 'No products found.', $this->logger->last_info );
	}

	// ------------------------------------------------------------------
	// legacy
	// ------------------------------------------------------------------

	public function test_legacy_shows_licenses(): void {
		add_filter(
			'stellarwp/uplink/legacy_licenses',
			static function () {
				return [
					[
						'key'        => 'ABC123',
						'slug'       => 'my-plugin',
						'name'       => 'My Plugin',
						'product'    => 'My Product',
						'is_active'  => true,
						'page_url'   => 'https://example.com/account',
						'expires_at' => '2027-01-01',
					],
				];
			}
		);

		$manager = $this->makeEmpty( License_Manager::class );
		$command = new License_Command( $manager, $this->site_data, $this->legacy_repository );

		$items = $this->run_legacy_json( $command );

		$this->assertCount( 1, $items );
		$this->assertSame( 'my-plugin', $items[0]['slug'] );
		$this->assertSame( 'My Plugin', $items[0]['name'] );
		$this->assertSame( 'My Product', $items[0]['product'] );
		$this->assertSame( 'ABC123', $items[0]['key'] );
		$this->assertTrue( $items[0]['is_active'] );
		$this->assertSame( '2027-01-01', $items[0]['expires_at'] );
	}

	public function test_legacy_logs_message_when_empty(): void {
		$manager = $this->makeEmpty( License_Manager::class );
		$command = new License_Command( $manager, $this->site_data, $this->legacy_repository );
		$command->legacy( [], [] );

		$this->assertSame( 'No legacy licenses found.', $this->logger->last_info );
	}

	// ------------------------------------------------------------------
	// Helpers
	// ------------------------------------------------------------------

	/**
	 * Runs get with --format=json and returns decoded product items.
	 *
	 * @param License_Command $command
	 *
	 * @return list<array<string, mixed>>
	 */
	private function run_get_json( License_Command $command ): array {
		ob_start();
		$command->get(
			[],
			[
				'format' => 'json',
				'fields' => 'product_slug,tier,status,expires,site_limit,active_count,over_limit,installed_here,validation_status,is_valid,pending_tier',
			]
		);
		$output = ob_get_clean();

		$decoded = json_decode( (string) $output, true );

		return is_array( $decoded ) ? $decoded : [];
	}

	/**
	 * Runs legacy with --format=json and returns decoded license items.
	 *
	 * @param License_Command $command
	 *
	 * @return list<array<string, mixed>>
	 */
	private function run_legacy_json( License_Command $command ): array {
		ob_start();
		$command->legacy(
			[],
			[
				'format' => 'json',
				'fields' => 'slug,name,product,key,is_active,expires_at,page_url',
			]
		);
		$output = ob_get_clean();

		$decoded = json_decode( (string) $output, true );

		return is_array( $decoded ) ? $decoded : [];
	}

	/**
	 * Runs lookup with --format=json and returns decoded product items.
	 *
	 * @param License_Command $command
	 * @param string          $key
	 *
	 * @return list<array<string, mixed>>
	 */
	private function run_lookup_json( License_Command $command, string $key ): array {
		ob_start();
		$command->lookup( [ $key ], [ 'format' => 'json' ] );
		$output = ob_get_clean();

		$decoded = json_decode( (string) $output, true );

		return is_array( $decoded ) ? $decoded : [];
	}
}
