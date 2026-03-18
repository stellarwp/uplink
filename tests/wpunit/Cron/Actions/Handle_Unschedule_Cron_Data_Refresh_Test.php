<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Cron\Actions;

use StellarWP\Uplink\Catalog\Catalog_Collection;
use StellarWP\Uplink\Catalog\Catalog_Repository;
use StellarWP\Uplink\Cron\Actions\Handle_Unschedule_Cron_Data_Refresh;
use StellarWP\Uplink\Cron\ValueObjects\CronHook;
use StellarWP\Uplink\Tests\UplinkTestCase;
use WP_Error;

final class Handle_Unschedule_Cron_Data_Refresh_Test extends UplinkTestCase {

	public function test_does_not_unschedule_when_catalog_returns_error(): void {
		wp_schedule_event( time(), 'twicedaily', CronHook::DATA_REFRESH );

		$catalog = $this->makeEmpty(
			Catalog_Repository::class,
			[ 'get' => new WP_Error( 'catalog_error', 'API unavailable.' ) ]
		);

		$action = new Handle_Unschedule_Cron_Data_Refresh( $catalog );
		( $action )();

		$this->assertNotFalse( wp_next_scheduled( CronHook::DATA_REFRESH ) );
	}

	public function test_does_not_unschedule_when_catalog_has_no_plugin_features(): void {
		wp_schedule_event( time(), 'twicedaily', CronHook::DATA_REFRESH );

		$catalog = $this->makeEmpty(
			Catalog_Repository::class,
			[ 'get' => Catalog_Collection::from_array( [] ) ]
		);

		$action = new Handle_Unschedule_Cron_Data_Refresh( $catalog );
		( $action )();

		$this->assertNotFalse( wp_next_scheduled( CronHook::DATA_REFRESH ) );
	}

	public function test_does_not_unschedule_when_catalog_plugin_is_active(): void {
		wp_schedule_event( time(), 'twicedaily', CronHook::DATA_REFRESH );
		update_option( 'active_plugins', [ 'give/give.php' ] );

		$catalog = $this->makeEmpty(
			Catalog_Repository::class,
			[ 'get' => $this->make_catalog_with_plugin( 'give/give.php' ) ]
		);

		$action = new Handle_Unschedule_Cron_Data_Refresh( $catalog );
		( $action )();

		$this->assertNotFalse( wp_next_scheduled( CronHook::DATA_REFRESH ) );
	}

	public function test_unschedules_when_all_catalog_plugins_inactive(): void {
		wp_schedule_event( time(), 'twicedaily', CronHook::DATA_REFRESH );
		update_option( 'active_plugins', [] );

		$catalog = $this->makeEmpty(
			Catalog_Repository::class,
			[ 'get' => $this->make_catalog_with_plugin( 'give/give.php' ) ]
		);

		$action = new Handle_Unschedule_Cron_Data_Refresh( $catalog );
		( $action )();

		$this->assertFalse( wp_next_scheduled( CronHook::DATA_REFRESH ) );
	}

	/**
	 * Build a minimal catalog collection containing one plugin feature.
	 *
	 * @param string $plugin_file Plugin basename, e.g. 'give/give.php'.
	 *
	 * @return Catalog_Collection
	 */
	private function make_catalog_with_plugin( string $plugin_file ): Catalog_Collection {
		return Catalog_Collection::from_array( [
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
		] );
	}
}
