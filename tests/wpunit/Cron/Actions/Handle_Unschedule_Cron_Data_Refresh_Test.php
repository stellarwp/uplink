<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Cron\Actions;

use StellarWP\Uplink\Catalog\Catalog_Collection;
use StellarWP\Uplink\Catalog\Catalog_Repository;
use StellarWP\Uplink\Cron\Actions\Handle_Unschedule_Cron_Data_Refresh;
use StellarWP\Uplink\Cron\ValueObjects\CronHook;
use StellarWP\Uplink\Tests\UplinkTestCase;

/**
 * Tests the Handle_Unschedule_Cron_Data_Refresh action.
 *
 * @since 3.0.0
 */
final class Handle_Unschedule_Cron_Data_Refresh_Test extends UplinkTestCase {

	/**
	 * Test that the action does not unschedule when no catalog is cached (e.g. never fetched or error).
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function test_does_not_unschedule_when_no_catalog_cached(): void {
		wp_schedule_event( time(), 'twicedaily', CronHook::DATA_REFRESH );

		$catalog = $this->makeEmpty(
			Catalog_Repository::class,
			[ 'get_cached' => null ]
		);

		$action = new Handle_Unschedule_Cron_Data_Refresh( $catalog );
		( $action )();

		$this->assertNotFalse( wp_next_scheduled( CronHook::DATA_REFRESH ) );
	}

	/**
	 * Test that the action does not unschedule when the catalog has no installable features.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function test_does_not_unschedule_when_catalog_has_no_installable_features(): void {
		wp_schedule_event( time(), 'twicedaily', CronHook::DATA_REFRESH );

		$catalog = $this->makeEmpty(
			Catalog_Repository::class,
			[ 'get_cached' => Catalog_Collection::from_array( [] ) ]
		);

		$action = new Handle_Unschedule_Cron_Data_Refresh( $catalog );
		( $action )();

		$this->assertNotFalse( wp_next_scheduled( CronHook::DATA_REFRESH ) );
	}

	/**
	 * Test that the action does not unschedule when the catalog plugin is active.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function test_does_not_unschedule_when_catalog_plugin_is_active(): void {
		wp_schedule_event( time(), 'twicedaily', CronHook::DATA_REFRESH );
		update_option( 'active_plugins', [ 'give/give.php' ] );

		$catalog = $this->makeEmpty(
			Catalog_Repository::class,
			[ 'get_cached' => $this->make_catalog_with_plugin( 'give/give.php' ) ]
		);

		$action = new Handle_Unschedule_Cron_Data_Refresh( $catalog );
		( $action )();

		$this->assertNotFalse( wp_next_scheduled( CronHook::DATA_REFRESH ) );
	}

	/**
	 * Test that the action unschedules when all catalog plugins are inactive.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function test_unschedules_when_all_catalog_plugins_inactive(): void {
		wp_schedule_event( time(), 'twicedaily', CronHook::DATA_REFRESH );
		update_option( 'active_plugins', [] );

		$catalog = $this->makeEmpty(
			Catalog_Repository::class,
			[ 'get_cached' => $this->make_catalog_with_plugin( 'give/give.php' ) ]
		);

		$action = new Handle_Unschedule_Cron_Data_Refresh( $catalog );
		( $action )();

		$this->assertFalse( wp_next_scheduled( CronHook::DATA_REFRESH ) );
	}

	/**
	 * Test that the action does not unschedule when the catalog theme is active.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function test_does_not_unschedule_when_catalog_theme_is_active(): void {
		wp_schedule_event( time(), 'twicedaily', CronHook::DATA_REFRESH );

		$active_theme_slug = get_stylesheet();

		$catalog = $this->makeEmpty(
			Catalog_Repository::class,
			[ 'get_cached' => $this->make_catalog_with_theme( $active_theme_slug ) ]
		);

		$action = new Handle_Unschedule_Cron_Data_Refresh( $catalog );
		( $action )();

		$this->assertNotFalse( wp_next_scheduled( CronHook::DATA_REFRESH ) );
	}

	/**
	 * Test that the action unschedules when the catalog theme is not active.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function test_unschedules_when_catalog_theme_is_inactive(): void {
		wp_schedule_event( time(), 'twicedaily', CronHook::DATA_REFRESH );

		$catalog = $this->makeEmpty(
			Catalog_Repository::class,
			[ 'get_cached' => $this->make_catalog_with_theme( 'some-inactive-theme' ) ]
		);

		$action = new Handle_Unschedule_Cron_Data_Refresh( $catalog );
		( $action )();

		$this->assertFalse( wp_next_scheduled( CronHook::DATA_REFRESH ) );
	}

	/**
	 * Build a minimal catalog collection containing one plugin feature.
	 *
	 * @since 3.0.0
	 *
	 * @param string $plugin_file Plugin basename, e.g. 'give/give.php'.
	 *
	 * @return Catalog_Collection
	 */
	private function make_catalog_with_plugin( string $plugin_file ): Catalog_Collection {
		return Catalog_Collection::from_array(
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
	}

	/**
	 * Build a minimal catalog collection containing one theme feature.
	 *
	 * @since 3.0.0
	 *
	 * @param string $theme_slug Theme stylesheet slug, e.g. 'twentytwentyfour'.
	 *
	 * @return Catalog_Collection
	 */
	private function make_catalog_with_theme( string $theme_slug ): Catalog_Collection {
		return Catalog_Collection::from_array(
			[
				[
					'product_slug' => 'test-product',
					'tiers'        => [],
					'features'     => [
						[
							'feature_slug'      => $theme_slug,
							'type'              => 'theme',
							'minimum_tier'      => '',
							'plugin_file'       => null,
							'is_dot_org'        => false,
							'name'              => 'Test Theme',
							'description'       => '',
							'category'          => '',
							'documentation_url' => '',
						],
					],
				],
			]
		);
	}
}
