<?php

namespace wpunit\Admin;

use StellarWP\Uplink\Admin\Update_Prevention;
use StellarWP\Uplink\Register;
use StellarWP\Uplink\Tests\UplinkTestCase;
use StellarWP\Uplink\Uplink;

class Update_PreventionTest extends UplinkTestCase {
	public $resource;
	public $container;
	public $path;

	public function setUp() {
		parent::setUp();

		$root 		    = dirname( __DIR__, 3 );
		$this->path     = $root . '/plugin.php';

		$this->resource = Register::plugin(
			'sample',
			'Lib Sample',
			$this->path,
			Uplink::class,
			'1.0.10',
			Uplink::class
		);
	}

	public function test_is_stellar_uplink_resource() {
		$update_prevention = new Update_Prevention();
		codecept_debug($this->path);

		$this->assertTrue( $update_prevention->is_stellar_uplink_resource( $this->path ) );
		$this->assertFalse( $update_prevention->is_stellar_uplink_resource( 'sample/index.php' ) );
	}

	public function test_filter_upgrader_source_selection() {
		$update_prevention = new Update_Prevention();
		$test_source 	   = 'https://test.source';
		$upgrader		   = new \stdClass();

		$this->assertSame( $test_source, $update_prevention->filter_upgrader_source_selection(
			$test_source,
			'',
			$upgrader,
			[]
		), 'It should return the same source if extras is empty or $extras["plugin"] does not exist' );

		$this->assertSame( $test_source, $update_prevention->filter_upgrader_source_selection(
			$test_source,
			'',
			$upgrader,
			[ 'plugin' => 'sample/index.php' ]
		), 'It should return the same source if it is not a stellar uplink resource' );

		add_filter( 'stellar_uplink_should_prevent_update_without_license', '__return_false' );

		$this->assertSame( $test_source, $update_prevention->filter_upgrader_source_selection(
			$test_source,
			'',
			$upgrader,
			[ 'plugin' => $this->path ]
		), 'It should return the same source if there are no incompatible plugins' );

		$this->assertSame( $test_source, $update_prevention->filter_upgrader_source_selection(
			$test_source,
			'',
			$upgrader,
			[ 'plugin' => $this->path ]
		), 'It should return the same source if we do not prevent the update' );
	}

}
