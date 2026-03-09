<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\CLI\Commands;

use StellarWP\Uplink\CLI\Commands\Feature as Feature_Command;
use StellarWP\Uplink\Features\Feature_Collection;
use StellarWP\Uplink\Features\Feature_Repository;
use StellarWP\Uplink\Features\Contracts\Strategy;
use StellarWP\Uplink\Features\Manager;
use StellarWP\Uplink\Features\Strategy\Strategy_Factory;
use StellarWP\Uplink\Features\Types\Flag;
use StellarWP\Uplink\Features\Types\Plugin;
use StellarWP\Uplink\Tests\CLI\Spy_Logger;
use StellarWP\Uplink\Tests\Traits\With_Uopz;
use StellarWP\Uplink\Tests\UplinkTestCase;
use WP_CLI;
use WP_Error;

/**
 * Tests for the WP-CLI `wp uplink feature` command.
 *
 * Uses a spy logger to capture WP_CLI output and uopz to prevent
 * exit() from killing the test process.
 *
 * @since 3.0.0
 */
final class FeatureTest extends UplinkTestCase {

	use With_Uopz;

	/** @var Manager */
	private Manager $manager;

	/** @var Feature_Command */
	private Feature_Command $command;

	/** @var Feature_Collection */
	private Feature_Collection $collection;

	/** @var Strategy */
	private $mock_strategy;

	/** @var Spy_Logger */
	private Spy_Logger $logger;

	protected function setUp(): void {
		parent::setUp();

		if ( function_exists( 'uopz_allow_exit' ) ) {
			uopz_allow_exit( false );
		}

		// WP_CLI\Formatter depends on functions from utils.php that aren't autoloaded.
		$utils_file = dirname( ( new \ReflectionClass( WP_CLI::class ) )->getFileName() ) . '/utils.php';
		if ( file_exists( $utils_file ) ) {
			require_once $utils_file;
		}

		$this->logger = new Spy_Logger();
		WP_CLI::set_logger( $this->logger );

		$this->collection = new Feature_Collection();
		$this->collection->add(
			Flag::from_array(
				[
					'slug'              => 'test-flag',
					'name'              => 'Test Flag',
					'description'       => 'A test flag feature.',
					'group'             => 'TestGroup',
					'tier'              => 'Tier 1',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs/test-flag',
				]
			)
		);
		$this->collection->add(
			Plugin::from_array(
				[
					'slug'              => 'test-plugin',
					'name'              => 'Test Plugin',
					'description'       => 'A test plugin feature.',
					'group'             => 'OtherGroup',
					'tier'              => 'Tier 2',
					'is_available'      => false,
					'documentation_url' => 'https://example.com/docs/test-plugin',
					'plugin_file'       => 'test-plugin/test-plugin.php',
					'plugin_slug'       => 'test-plugin',
					'authors'           => [ 'StellarWP' ],
					'is_dot_org'        => true,
				]
			)
		);

		$this->mock_strategy = $this->makeEmpty(
			Strategy::class,
			[
				'enable'    => true,
				'disable'   => true,
				'is_active' => true,
			]
		);

		$repository = $this->makeEmpty(
			Feature_Repository::class,
			[
				'get' => $this->collection,
			]
		);

		$factory = $this->makeEmpty(
			Strategy_Factory::class,
			[
				'make' => $this->mock_strategy,
			]
		);

		$this->manager = new Manager( $repository, $factory, 'test-key', 'example.com' );
		$this->command = new Feature_Command( $this->manager );
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

	public function test_list_outputs_all_features_as_json(): void {
		$items = $this->run_list_json( [] );

		$this->assertCount( 2, $items );
		$this->assertSame( 'test-flag', $items[0]['slug'] );
		$this->assertSame( 'test-plugin', $items[1]['slug'] );
	}

	public function test_list_calls_error_on_wp_error(): void {
		$repository = $this->makeEmpty(
			Feature_Repository::class,
			[
				'get' => new WP_Error( 'api_error', 'Could not fetch features.' ),
			]
		);
		$factory    = $this->makeEmpty( Strategy_Factory::class );
		$manager    = new Manager( $repository, $factory, 'test-key', 'example.com' );
		$command    = new Feature_Command( $manager );

		$command->list_( [], [] );

		$this->assertSame( 'Could not fetch features.', $this->logger->last_error );
	}

	public function test_list_filters_by_group(): void {
		$items = $this->run_list_json( [ 'group' => 'TestGroup' ] );

		$this->assertCount( 1, $items );
		$this->assertSame( 'test-flag', $items[0]['slug'] );
	}

	public function test_list_filters_by_type(): void {
		$items = $this->run_list_json( [ 'type' => 'flag' ] );

		$this->assertCount( 1, $items );
		$this->assertSame( 'test-flag', $items[0]['slug'] );
	}

	public function test_list_filters_by_available(): void {
		$items = $this->run_list_json( [ 'available' => 'true' ] );

		$this->assertCount( 1, $items );
		$this->assertSame( 'test-flag', $items[0]['slug'] );
	}

	public function test_list_filters_by_tier(): void {
		$items = $this->run_list_json( [ 'tier' => 'Tier 1' ] );

		$this->assertCount( 1, $items );
		$this->assertSame( 'test-flag', $items[0]['slug'] );
	}

	public function test_list_filters_combine(): void {
		$items = $this->run_list_json(
			[
				'group'     => 'TestGroup',
				'type'      => 'flag',
				'available' => 'true',
				'tier'      => 'Tier 1',
			]
		);

		$this->assertCount( 1, $items );
		$this->assertSame( 'test-flag', $items[0]['slug'] );
	}

	public function test_list_filters_return_empty_when_nothing_matches(): void {
		$items = $this->run_list_json( [ 'group' => 'NonexistentGroup' ] );

		$this->assertSame( [], $items );
	}

	// ------------------------------------------------------------------
	// get
	// ------------------------------------------------------------------

	public function test_get_outputs_feature_as_json(): void {
		$item = $this->run_get_json( 'test-flag' );

		$this->assertSame( 'test-flag', $item['slug'] );
		$this->assertSame( 'Test Flag', $item['name'] );
		$this->assertSame( 'true', $item['is_available'] );
		$this->assertSame( 'true', $item['is_enabled'] );
	}

	public function test_get_calls_error_for_nonexistent_feature(): void {
		$this->command->get( [ 'nonexistent' ], [] );

		$this->assertSame( 'Feature "nonexistent" not found.', $this->logger->last_error );
	}

	public function test_get_respects_fields_parameter(): void {
		$item = $this->run_get_json( 'test-flag', 'slug,name' );

		$this->assertArrayHasKey( 'slug', $item );
		$this->assertArrayHasKey( 'name', $item );
		$this->assertArrayNotHasKey( 'group', $item );
		$this->assertArrayNotHasKey( 'tier', $item );
	}

	public function test_get_outputs_plugin_feature_with_plugin_fields(): void {
		$item = $this->run_get_json( 'test-plugin' );

		$this->assertSame( 'test-plugin/test-plugin.php', $item['plugin_file'] );
		$this->assertSame( 'test-plugin', $item['plugin_slug'] );
		$this->assertSame( 'StellarWP', $item['authors'] );
		$this->assertSame( 'true', $item['is_dot_org'] );
	}

	// ------------------------------------------------------------------
	// enable
	// ------------------------------------------------------------------

	public function test_enable_calls_success_on_valid_feature(): void {
		$this->command->enable( [ 'test-flag' ], [] );

		$this->assertSame( 'Feature "test-flag" enabled.', $this->logger->last_success );
		$this->assertNull( $this->logger->last_error );
	}

	public function test_enable_calls_error_for_nonexistent_feature(): void {
		$this->command->enable( [ 'nonexistent' ], [] );

		$this->assertStringContainsString( 'nonexistent', $this->logger->last_error );
	}

	public function test_enable_calls_error_on_strategy_failure(): void {
		$command = $this->make_command_with_strategy( [ 'enable' => new WP_Error( 'fail', 'Could not enable feature.' ) ] );

		$command->enable( [ 'test-flag' ], [] );

		$this->assertSame( 'Could not enable feature.', $this->logger->last_error );
	}

	// ------------------------------------------------------------------
	// disable
	// ------------------------------------------------------------------

	public function test_disable_calls_success_on_valid_feature(): void {
		$this->command->disable( [ 'test-flag' ], [] );

		$this->assertSame( 'Feature "test-flag" disabled.', $this->logger->last_success );
		$this->assertNull( $this->logger->last_error );
	}

	public function test_disable_calls_error_for_nonexistent_feature(): void {
		$this->command->disable( [ 'nonexistent' ], [] );

		$this->assertStringContainsString( 'nonexistent', $this->logger->last_error );
	}

	public function test_disable_calls_error_on_strategy_failure(): void {
		$command = $this->make_command_with_strategy( [ 'disable' => new WP_Error( 'fail', 'Could not disable feature.' ) ] );

		$command->disable( [ 'test-flag' ], [] );

		$this->assertSame( 'Could not disable feature.', $this->logger->last_error );
	}

	// ------------------------------------------------------------------
	// is_enabled
	// ------------------------------------------------------------------

	public function test_is_enabled_logs_enabled_for_active_feature(): void {
		$this->command->is_enabled( [ 'test-flag' ], [] );

		$this->assertSame( 'Feature "test-flag" is enabled.', $this->logger->last_info );
	}

	public function test_is_enabled_logs_not_enabled_when_strategy_inactive(): void {
		$command = $this->make_command_with_strategy( [ 'is_active' => false ] );

		$command->is_enabled( [ 'test-flag' ], [] );

		$this->assertSame( 'Feature "test-flag" is not enabled.', $this->logger->last_info );
	}

	public function test_is_enabled_logs_not_found_for_nonexistent_feature(): void {
		$this->command->is_enabled( [ 'nonexistent' ], [] );

		// The first log message is "not found"; execution continues past exit()
		// because uopz suppresses it, so we check the first message logged.
		$this->assertNotEmpty( $this->logger->info_messages );
		$this->assertSame( 'Feature "nonexistent" not found.', $this->logger->info_messages[0] );
	}

	// ------------------------------------------------------------------
	// feature_to_display_item (private, via reflection)
	// ------------------------------------------------------------------

	public function test_feature_to_display_item_converts_booleans(): void {
		$feature = $this->collection->get( 'test-flag' );
		$this->assertNotNull( $feature );

		/** @var array<string, mixed> $item */
		$item = $this->invoke_feature_to_display_item( $this->command, $feature );

		$this->assertSame( 'true', $item['is_available'] );
		$this->assertSame( 'true', $item['is_enabled'] );
		$this->assertSame( 'false', $item['is_dot_org'] );
		$this->assertSame( 'test-flag', $item['slug'] );
	}

	public function test_feature_to_display_item_unavailable_feature(): void {
		$feature = $this->collection->get( 'test-plugin' );
		$this->assertNotNull( $feature );

		/** @var array<string, mixed> $item */
		$item = $this->invoke_feature_to_display_item( $this->command, $feature );

		$this->assertSame( 'false', $item['is_available'] );
	}

	public function test_feature_to_display_item_joins_authors(): void {
		$plugin = Plugin::from_array(
			[
				'slug'         => 'test-plugin',
				'name'         => 'Test Plugin',
				'group'        => 'OtherGroup',
				'tier'         => 'Tier 2',
				'is_available' => true,
				'plugin_file'  => 'test-plugin/test-plugin.php',
				'plugin_slug'  => 'test-plugin',
				'authors'      => [ 'Alice', 'Bob' ],
				'is_dot_org'   => false,
			]
		);

		/** @var array<string, mixed> $item */
		$item = $this->invoke_feature_to_display_item( $this->command, $plugin );

		$this->assertSame( 'Alice, Bob', $item['authors'] );
	}

	public function test_feature_to_display_item_dot_org_true(): void {
		$feature = $this->collection->get( 'test-plugin' );
		$this->assertNotNull( $feature );

		/** @var array<string, mixed> $item */
		$item = $this->invoke_feature_to_display_item( $this->command, $feature );

		$this->assertSame( 'true', $item['is_dot_org'] );
	}

	public function test_feature_to_display_item_resolves_enabled_via_manager(): void {
		$feature = $this->collection->get( 'test-flag' );
		$this->assertNotNull( $feature );

		/** @var array<string, mixed> $item */
		$item = $this->invoke_feature_to_display_item( $this->command, $feature );

		$this->assertSame( 'true', $item['is_enabled'] );
	}

	public function test_feature_to_display_item_shows_disabled_when_strategy_inactive(): void {
		$strategy   = $this->makeEmpty( Strategy::class, [ 'is_active' => false ] );
		$factory    = $this->makeEmpty( Strategy_Factory::class, [ 'make' => $strategy ] );
		$repository = $this->makeEmpty( Feature_Repository::class, [ 'get' => $this->collection ] );
		$manager    = new Manager( $repository, $factory, 'test-key', 'example.com' );
		$command    = new Feature_Command( $manager );

		$feature = $this->collection->get( 'test-flag' );
		$this->assertNotNull( $feature );

		/** @var array<string, mixed> $item */
		$item = $this->invoke_feature_to_display_item( $command, $feature );

		$this->assertSame( 'false', $item['is_enabled'] );
	}

	// ------------------------------------------------------------------
	// collection_to_display_items (private, via reflection)
	// ------------------------------------------------------------------

	public function test_collection_to_display_items_maps_all_features(): void {
		/** @var list<array<string, mixed>> $items */
		$items = $this->invoke_collection_to_display_items( $this->command, $this->collection );

		$this->assertCount( 2, $items );
		$this->assertSame( 'test-flag', $items[0]['slug'] );
		$this->assertSame( 'test-plugin', $items[1]['slug'] );
	}

	public function test_collection_to_display_items_empty_collection(): void {
		/** @var list<array<string, mixed>> $items */
		$items = $this->invoke_collection_to_display_items( $this->command, new Feature_Collection() );

		$this->assertSame( [], $items );
	}

	// ------------------------------------------------------------------
	// Helpers
	// ------------------------------------------------------------------

	/**
	 * Runs list_ with --format=json and returns the decoded array.
	 *
	 * @param array<string, string> $filters Associative args (group, type, etc.).
	 *
	 * @return list<array<string, mixed>>
	 */
	private function run_list_json( array $filters ): array {
		$assoc_args = array_merge( $filters, [ 'format' => 'json' ] );

		ob_start();
		$this->command->list_( [], $assoc_args );
		$output = ob_get_clean();

		$decoded = json_decode( (string) $output, true );

		return is_array( $decoded ) ? $decoded : [];
	}

	/**
	 * Runs get with --format=json and returns the decoded item.
	 *
	 * @param string      $slug   Feature slug.
	 * @param string|null $fields Optional comma-separated fields.
	 *
	 * @return array<string, mixed>
	 */
	private function run_get_json( string $slug, ?string $fields = null ): array {
		$assoc_args = [ 'format' => 'json' ];

		if ( $fields !== null ) {
			$assoc_args['fields'] = $fields;
		}

		ob_start();
		$this->command->get( [ $slug ], $assoc_args );
		$output = ob_get_clean();

		$decoded = json_decode( (string) $output, true );

		return is_array( $decoded ) ? $decoded : [];
	}

	/**
	 * Creates a Feature_Command with a custom strategy mock.
	 *
	 * @param array<string, mixed> $strategy_returns Mock return values for Strategy methods.
	 *
	 * @return Feature_Command
	 */
	private function make_command_with_strategy( array $strategy_returns ): Feature_Command {
		$strategy   = $this->makeEmpty( Strategy::class, $strategy_returns );
		$factory    = $this->makeEmpty( Strategy_Factory::class, [ 'make' => $strategy ] );
		$repository = $this->makeEmpty( Feature_Repository::class, [ 'get' => $this->collection ] );
		$manager    = new Manager( $repository, $factory, 'test-key', 'example.com' );

		return new Feature_Command( $manager );
	}

	/**
	 * @param Feature_Command                          $command
	 * @param \StellarWP\Uplink\Features\Types\Feature $feature
	 *
	 * @return mixed
	 */
	private function invoke_feature_to_display_item( Feature_Command $command, \StellarWP\Uplink\Features\Types\Feature $feature ) {
		$method = new \ReflectionMethod( Feature_Command::class, 'feature_to_display_item' );
		$method->setAccessible( true );

		return $method->invoke( $command, $feature );
	}

	/**
	 * @param Feature_Command    $command
	 * @param Feature_Collection $collection
	 *
	 * @return mixed
	 */
	private function invoke_collection_to_display_items( Feature_Command $command, Feature_Collection $collection ) {
		$method = new \ReflectionMethod( Feature_Command::class, 'collection_to_display_items' );
		$method->setAccessible( true );

		return $method->invoke( $command, $collection );
	}
}
