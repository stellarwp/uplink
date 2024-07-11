<?php declare( strict_types=1 );

namespace wpunit;

use StellarWP\Uplink\Config;
use StellarWP\Uplink\Tests\UplinkTestCase;
use StellarWP\Uplink\Register;
use StellarWP\Uplink\Resources\Collection;
use StellarWP\Uplink\Tests\TestUtils;

class RegisterTest extends UplinkTestCase {

	use TestUtils;

	public function resourceProvider() {
		$resources = $this->get_test_resources();

		foreach ( $resources as $resource ) {
			yield [ $resource ];
		}
	}

	/**
	 * @test
	 * @dataProvider resourceProvider
	 */
	public function it_should_register_resource( $resource ) {
		$collection = Config::get_container()->get( Collection::class );

		$this->assertFalse( $collection->offsetExists( $resource['slug'] ) );

		$is_oauth = 'service' === $resource['type'] ? true : false;

		Register::{$resource['type']}(
			$resource['slug'],
			$resource['name'],
			$resource['version'],
			$resource['path'],
			$resource['class'],
			null,
			$is_oauth,
		);

		$this->assertTrue( $collection->offsetExists( $resource['slug'] ) );

		$this->assertEquals( $is_oauth, $collection->get( $resource['slug'] )->is_using_oauth() );
	}
}
