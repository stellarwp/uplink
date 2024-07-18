<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Storage\Drivers;

use StellarWP\Uplink\Storage\Contracts\Storage;
use StellarWP\Uplink\Storage\Drivers\Option_Storage;
use StellarWP\Uplink\Storage\Drivers\Transient_Storage;
use StellarWP\Uplink\Storage\Exceptions\Invalid_Key_Exception;
use StellarWP\Uplink\Tests\UplinkTestCase;

final class StorageKeyTest extends UplinkTestCase {

	/**
	 * @dataProvider compositeProvider
	 *
	 * @param  mixed    $key     The storage key.
	 * @param  mixed    $data    The data to store.
	 * @param  Storage  $driver  The concrete storage driver.
	 */
	public function test_it_stores_various_types_of_data_and_keys( $key, $data, Storage $driver ) {
		$expire = 10;

		$this->assertTrue( $driver->set( $key, $data, $expire ) );
		$this->assertEquals( $data, $driver->get( $key ) );
		$this->assertTrue( $driver->delete( $key ) );
		$this->assertNull( $driver->get( $key ) );
	}

	/**
	 * Data provider for storage classes.
	 *
	 * @return array<string, array<Storage>>
	 */
	public function storageProvider(): array {
		return [
			'option_storage'    => [ new Option_Storage( 'uplink_test_storage' ) ],
			'Transient Storage' => [ new Transient_Storage() ],
		];
	}

	/**
	 * Date provider for multiple key/data type combinations.
	 *
	 * @return array<string, array{0: mixed, 1: mixed}>
	 */
	public function keyProvider(): array {
		$keys = [
			'string key'  => 'test_string',
			'integer key' => 12345,
			'float key'   => 12345.6789,
			'array key'   => [
				'some' => 'key',
				'more' => true,
				'one'  => 1,
				'two'  => 2.0,
			],
			'object key'  => (object) [
				'propertyA' => 'valueA',
				'propertyB' => 'valueB',
				'propertyC' => (object) [
					'propertyA' => 'valueA',
					'propertyB' => 'valueB',
				],
				'propertyD' => [
					'one' => 'one',
					'two' => 'two',
				],
			],
		];

		$data = [
			'string data'        => 'Hello World',
			'integer data'       => 56789,
			'float data'         => 56789.12345,
			'boolean data true'  => true,
			'boolean data false' => false,
			'array data'         => [
				'test' => true,
				'one'  => 1,
				'two'  => 'two',
			],
			'object data'        => (object) [
				'propertyA' => 'valueA',
				'propertyB' => 'valueB',
				'propertyC' => (object) [
					'propertyA' => 'valueA',
					'propertyB' => 'valueB',
				],
			],
		];

		$test_cases = [];

		foreach ( $keys as $key_type => $key ) {
			foreach ( $data as $data_type => $data_value ) {
				$test_cases[ "$key_type and $data_type" ] = [ $key, $data_value ];
			}
		}

		return $test_cases;
	}

	/**
	 * @return array<string, array{0: mixed, 1: mixed, 2: Storage}>
	 */
	public function compositeProvider(): array {
		$test_cases = [];

		foreach ( $this->keyProvider() as $key_and_data_description => $key_and_data ) {
			foreach ( $this->storageProvider() as $driver_type => $driver ) {
				$description               = "$key_and_data_description with $driver_type";
				$test_cases[ $description ] = array_merge( $key_and_data, $driver );
			}
		}

		return $test_cases;
	}

}
