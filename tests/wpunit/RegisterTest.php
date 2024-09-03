<?php declare( strict_types=1 );

namespace wpunit;

use StellarWP\Uplink\Config;
use StellarWP\Uplink\Resources\Resource;
use StellarWP\Uplink\Tests\UplinkTestCase;
use StellarWP\Uplink\Register;
use StellarWP\Uplink\Resources\Collection;
use StellarWP\Uplink\Tests\TestUtils;
use StellarWP\Uplink\Uplink;

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
			$is_oauth
		);

		$this->assertTrue( $collection->offsetExists( $resource['slug'] ) );

		$this->assertEquals( $is_oauth, $collection->get( $resource['slug'] )->is_using_oauth() );
	}

	/**
	 * @test
	 */
	public function is_should_register_service_with_oauth_using_int():void{
		$collection = Config::get_container()->get( Collection::class );

		$this->assertFalse( $collection->offsetExists( 'service-with-oauth-int' ) );

		Register::service(
			'service-with-oauth-int',
			'Service With OAuth Int',
			'1.0.10',
			$this->get_base() . '/service-with-oauth-int.php',
			Uplink::class,
			Uplink::class,
			Resource::OAUTH_REQUIRED
		);

		$this->assertTrue( $collection->offsetExists( 'service-with-oauth-int' ) );
		$this->assertTrue( $collection->get( 'service-with-oauth-int' )->is_using_oauth() );
		$this->assertFalse( $collection->get( 'service-with-oauth-int' )->oauth_requires_license_key() );
	}

	public function is_should_register_service_with_oauth_and_license_key_using_int():void{
		$collection = Config::get_container()->get( Collection::class );

		$this->assertFalse( $collection->offsetExists( 'service-with-oauth-int-2' ) );
		$this->assertFalse( $collection->offsetExists( 'service-with-oauth-int-3' ) );

		// Include the Resource::OAUTH_REQUIRES_LICENSE_KEY flag.
		Register::service(
			'service-with-oauth-int-2',
			'Service With OAuth Int 2',
			'1.0.10',
			$this->get_base() . '/service-with-oauth-int-2.php',
			Uplink::class,
			Uplink::class,
			Resource::OAUTH_REQUIRED | Resource::OAUTH_REQUIRES_LICENSE_KEY
		);

		// Omit the Resource::OAUTH_REQUIRED flag, it should be implied.
		Register::service(
			'service-with-oauth-int-3',
			'Service With OAuth Int 3',
			'1.0.10',
			$this->get_base() . '/service-with-oauth-int-2.php',
			Uplink::class,
			Uplink::class,
			Resource::OAUTH_REQUIRES_LICENSE_KEY
		);

		$this->assertTrue( $collection->offsetExists( 'service-with-oauth-int-3' ) );
		$this->assertTrue( $collection->get( 'service-with-oauth-int-3' )->is_using_oauth() );
		$this->assertTrue( $collection->get( 'service-with-oauth-int-3' )->oauth_requires_license_key() );
	}
}
