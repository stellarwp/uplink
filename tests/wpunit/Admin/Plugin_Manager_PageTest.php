<?php declare( strict_types=1 );

namespace wpunit\Admin;

use StellarWP\Uplink\Admin\Plugin_Manager_Page;
use StellarWP\Uplink\Register;
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
			'uplink/tests/plugin.php',
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
}
