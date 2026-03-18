<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Cron;

use StellarWP\Uplink\Catalog\Catalog_Collection;
use StellarWP\Uplink\Catalog\Catalog_Repository;
use StellarWP\Uplink\Catalog\Clients\Catalog_Client;
use StellarWP\Uplink\Cron\Actions\Handle_Unschedule_Cron_Data_Refresh;
use StellarWP\Uplink\Cron\Jobs\Refresh_Catalog_Job;
use StellarWP\Uplink\Cron\Jobs\Refresh_License_Job;
use StellarWP\Uplink\Cron\ValueObjects\CronHook;
use StellarWP\Uplink\Licensing\Clients\Licensing_Client;
use StellarWP\Uplink\Tests\UplinkTestCase;

final class ProviderTest extends UplinkTestCase {

	protected function setUp(): void {
		parent::setUp();

		wp_clear_scheduled_hook( CronHook::DATA_REFRESH );

		$this->container->singleton(
			Catalog_Client::class,
			$this->makeEmpty( Catalog_Client::class )
		);

		$this->container->singleton(
			Licensing_Client::class,
			$this->makeEmpty( Licensing_Client::class )
		);
	}

	public function test_it_registers_refresh_catalog_job(): void {
		$this->assertInstanceOf(
			Refresh_Catalog_Job::class,
			$this->container->get( Refresh_Catalog_Job::class )
		);
	}

	public function test_it_registers_refresh_license_job(): void {
		$this->assertInstanceOf(
			Refresh_License_Job::class,
			$this->container->get( Refresh_License_Job::class )
		);
	}

	public function test_it_registers_handle_unschedule_action(): void {
		$this->assertInstanceOf(
			Handle_Unschedule_Cron_Data_Refresh::class,
			$this->container->get( Handle_Unschedule_Cron_Data_Refresh::class )
		);
	}

	public function test_jobs_are_singletons(): void {
		$this->assertSame(
			$this->container->get( Refresh_Catalog_Job::class ),
			$this->container->get( Refresh_Catalog_Job::class )
		);

		$this->assertSame(
			$this->container->get( Refresh_License_Job::class ),
			$this->container->get( Refresh_License_Job::class )
		);
	}

	public function test_cron_is_scheduled_on_init(): void {
		$this->assertFalse( wp_next_scheduled( CronHook::DATA_REFRESH ) );

		do_action( 'init' );

		$this->assertNotFalse( wp_next_scheduled( CronHook::DATA_REFRESH ) );
	}

	public function test_cron_unscheduled_when_no_catalog_plugins_remain_active(): void {
		$this->store_catalog_with_plugin( 'give/give.php' );
		wp_schedule_event( time(), 'twicedaily', CronHook::DATA_REFRESH );

		update_option( 'active_plugins', [] );

		do_action( 'deactivated_plugin', 'give/give.php' );

		$this->assertFalse( wp_next_scheduled( CronHook::DATA_REFRESH ) );
	}

	public function test_cron_not_unscheduled_when_catalog_plugin_still_active(): void {
		$this->store_catalog_with_plugin( 'give/give.php' );
		wp_schedule_event( time(), 'twicedaily', CronHook::DATA_REFRESH );

		update_option( 'active_plugins', [ 'give/give.php' ] );

		do_action( 'deactivated_plugin', 'some-other-plugin/plugin.php' );

		$this->assertNotFalse( wp_next_scheduled( CronHook::DATA_REFRESH ) );
	}

	public function test_cron_not_unscheduled_when_catalog_has_no_plugin_features(): void {
		$this->container->get( Catalog_Repository::class )->set_catalog(
			Catalog_Collection::from_array( [] )
		);
		wp_schedule_event( time(), 'twicedaily', CronHook::DATA_REFRESH );

		do_action( 'deactivated_plugin', 'any-plugin/plugin.php' );

		$this->assertNotFalse( wp_next_scheduled( CronHook::DATA_REFRESH ) );
	}

	/**
	 * Store a minimal catalog containing one plugin feature with the given plugin file.
	 *
	 * @param string $plugin_file Plugin basename, e.g. 'give/give.php'.
	 *
	 * @return void
	 */
	private function store_catalog_with_plugin( string $plugin_file ): void {
		$catalog = Catalog_Collection::from_array(
			[
				[
					'product_slug' => 'test-product',
					'tiers'        => [],
					'features'     => [
						[
							'feature_slug'      => 'test-feature',
							'type'              => 'plugin',
							'minimum_tier'      => '',
							'plugin_file'       => $plugin_file,
							'is_dot_org'        => false,
							'name'              => 'Test Feature',
							'description'       => '',
							'category'          => '',
							'documentation_url' => '',
						],
					],
				],
			] 
		);

		$this->container->get( Catalog_Repository::class )->set_catalog( $catalog );
	}
}
