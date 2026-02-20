<?php declare( strict_types=1 );

namespace wpunit\Admin;

use StellarWP\Uplink\Admin\Plugin_Manager_Page;
use StellarWP\Uplink\Register;
use StellarWP\Uplink\Resources\Plugin;
use StellarWP\Uplink\Tests\UplinkTestCase;
use StellarWP\Uplink\Uplink;

class Plugin_Manager_PageTest extends UplinkTestCase {

	/**
	 * @var Plugin_Manager_Page
	 */
	private $page;

	protected function setUp(): void {
		parent::setUp();

		$this->page = new Plugin_Manager_Page();

		Register::plugin(
			'manager-test',
			'Manager Test Plugin',
			'1.5.0',
			'manager-test/manager-test.php',
			Uplink::class
		);
	}

	/**
	 * @test
	 */
	public function it_should_render_when_it_has_the_highest_version(): void {
		$this->assertTrue( $this->page->should_render() );
	}

	/**
	 * @test
	 */
	public function it_should_not_render_when_a_higher_version_exists(): void {
		add_filter( 'stellarwp/uplink/highest_version', static function () {
			return '99.0.0';
		} );

		$this->assertFalse( $this->page->should_render() );
	}

	/**
	 * @test
	 */
	public function it_should_not_render_when_page_already_registered(): void {
		do_action( 'stellarwp/uplink/unified_ui_registered' );

		$this->assertFalse( $this->page->should_render() );
	}

	/**
	 * @test
	 */
	public function it_should_register_menu_page(): void {
		global $menu;
		$menu = [];

		set_current_screen( 'dashboard' );
		$this->page->maybe_register_page();

		$slugs = array_column( $menu, 2 );
		$this->assertContains( 'stellarwp-licenses', $slugs );
	}

	/**
	 * @test
	 */
	public function it_should_only_register_page_once(): void {
		global $menu;
		$menu = [];

		set_current_screen( 'dashboard' );

		$page_a = new Plugin_Manager_Page();
		$page_b = new Plugin_Manager_Page();

		$page_a->maybe_register_page();
		$page_b->maybe_register_page();

		$slugs = array_filter( array_column( $menu, 2 ), static function ( $s ) {
			return $s === 'stellarwp-licenses';
		} );

		$this->assertCount( 1, $slugs );
	}

	/**
	 * @test
	 */
	public function it_should_discover_resources_from_update_status(): void {
		$this->seed_update_status( 'manager-test', '1.5.0', 'Manager Test Plugin' );
		$this->seed_update_status( 'another-plugin', '2.0.0', 'Another Plugin' );

		$resources = $this->page->discover_resources();

		$this->assertArrayHasKey( 'manager-test', $resources );
		$this->assertArrayHasKey( 'another-plugin', $resources );

		$this->assertSame( 'Manager Test Plugin', $resources['manager-test']['name'] );
		$this->assertSame( '1.5.0', $resources['manager-test']['version'] );

		$this->assertSame( 'Another Plugin', $resources['another-plugin']['name'] );
		$this->assertSame( '2.0.0', $resources['another-plugin']['version'] );
	}

	/**
	 * @test
	 */
	public function it_should_fall_back_to_slug_when_update_has_no_name(): void {
		$this->seed_update_status( 'unnamed-plugin', '1.0.0' );

		$resources = $this->page->discover_resources();

		$this->assertSame( 'unnamed-plugin', $resources['unnamed-plugin']['name'] );
	}

	/**
	 * @test
	 */
	public function it_should_include_license_key_in_discovered_resources(): void {
		$this->seed_update_status( 'licensed-plugin', '3.0.0', 'Licensed Plugin' );
		update_option( 'stellarwp_uplink_license_key_licensed-plugin', 'my-license-key' );

		$resources = $this->page->discover_resources();

		$this->assertSame( 'my-license-key', $resources['licensed-plugin']['key'] );
	}

	/**
	 * @test
	 */
	public function it_should_include_license_status_in_discovered_resources(): void {
		$this->seed_update_status( 'status-plugin', '1.0.0', 'Status Plugin' );

		$site_domain = wp_parse_url( get_option( 'siteurl', '' ), PHP_URL_HOST ) ?: '';
		update_option( 'stellarwp_uplink_license_key_status_status-plugin_' . $site_domain, 'valid' );

		$resources = $this->page->discover_resources();

		$this->assertSame( 'valid', $resources['status-plugin']['status'] );
	}

	/**
	 * @test
	 */
	public function it_should_return_empty_when_no_update_status_options_exist(): void {
		$resources = $this->page->discover_resources();

		$this->assertEmpty( $resources );
	}

	/**
	 * @test
	 */
	public function it_should_render_table_with_resources(): void {
		$this->seed_update_status( 'render-test', '4.0.0', 'Render Test Plugin' );
		update_option( 'stellarwp_uplink_license_key_render-test', 'render-key-123' );

		ob_start();
		$this->page->render();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'StellarWP Licenses', $html );
		$this->assertStringContainsString( 'Render Test Plugin', $html );
		$this->assertStringContainsString( '4.0.0', $html );
		$this->assertStringContainsString( 'render-key-123', $html );
	}

	/**
	 * @test
	 */
	public function it_should_render_empty_state_when_no_resources(): void {
		ob_start();
		$this->page->render();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'No StellarWP products found', $html );
	}

	/**
	 * Seeds a stellarwp_uplink_update_status option for testing.
	 */
	private function seed_update_status( string $slug, string $version, string $name = '' ): void {
		$update = new \stdClass();

		if ( $name ) {
			$update->name = $name;
			$update->slug = $slug;
		}

		$status                  = new \stdClass();
		$status->last_check      = time();
		$status->checked_version = $version;
		$status->update          = $update;

		update_option( 'stellarwp_uplink_update_status_' . $slug, $status );
	}
}
