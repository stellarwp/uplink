<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Storage\Drivers;

use StellarWP\Uplink\Storage\Contracts\Storage;
use StellarWP\Uplink\Storage\Drivers\Option_Storage;
use StellarWP\Uplink\Storage\Drivers\Transient_Storage;
use StellarWP\Uplink\Storage\Exceptions\Invalid_Key_Exception;
use StellarWP\Uplink\Tests\UplinkTestCase;

final class StorageDriverTest extends UplinkTestCase {

	/**
	 * Default expiration.
	 *
	 * @var int
	 */
	private $expire = 10;

	protected function tearDown(): void {
		parent::tearDown();

		delete_option( 'uplink_test_storage' );
		delete_transient( 'uplink_test_key' );
	}

	/**
	 * Data provider for storage classes.
	 *
	 * @return array<array<Storage>>
	 */
	public function storageProvider(): array {
		$drivers = [
			[ new Transient_Storage() ],
			[ new Option_Storage( 'uplink_test_storage' ) ],
		];

		$test_cases = [];

		foreach ( $drivers as $d ) {
			foreach ( $d as $driver ) {
				$description                  = sprintf( 'with %s', get_class( $driver ) );
				$test_cases[ $description ][] = $driver;
			}
		}

		return $test_cases;
	}

	/**
	 * @dataProvider storageProvider
	 */
	public function test_it_sets_and_gets_values( Storage $storage ): void {
		$key   = 'uplink_test_key';
		$value = 'test';

		$this->assertTrue( $storage->set( $key, $value, $this->expire ) );
		$this->assertSame( $value, $storage->get( $key ) );
	}

	/**
	 * @dataProvider storageProvider
	 */
	public function test_it_deletes_values( Storage $storage ): void {
		$key   = 'uplink_test_key';
		$value = 'test';

		$this->assertTrue( $storage->set( $key, $value, $this->expire ) );
		$this->assertSame( $value, $storage->get( $key ) );
		$this->assertTrue( $storage->delete( $key ) );
		$this->assertNull( $storage->get( $key ) );
	}

	/**
	 * @dataProvider storageProvider
	 */
	public function test_it_remembers_values( Storage $storage ): void {
		$key   = 'uplink_test_key';
		$value = 'test';

		$stored_value = $storage->remember( $key, function () use ( $value ) {
			return $value;
		}, $this->expire );

		$this->assertSame( $value, $stored_value );
		$this->assertSame( $value, $storage->get( $key ) );
	}

	/**
	 * @dataProvider storageProvider
	 */
	public function test_it_pulls_value( Storage $storage ): void {
		$key   = 'uplink_test_key';
		$value = 'test';

		$this->assertTrue( $storage->set( $key, $value, $this->expire ) );
		$pulled_value = $storage->pull( $key );

		$this->assertSame( $value, $pulled_value );
		$this->assertNull( $storage->get( $key ) );
	}

	/**
	 * @dataProvider storageProvider
	 */
	public function test_it_throws_exception_with_invalid_string_storage_key( Storage $storage ): void {
		$this->expectException( Invalid_Key_Exception::class );

		$storage->get( '' );
	}

	/**
	 * @dataProvider storageProvider
	 */
	public function test_it_throws_exception_with_invalid_array_storage_key( Storage $storage ): void {
		$this->expectException( Invalid_Key_Exception::class );

		$storage->get( [] );
	}

	/**
	 * @dataProvider storageProvider
	 *
	 * Some bug here where WP_INSTALLING is defined when using --env multisite
	 * causing it to use local object cache.
	 *
	 * @env singlesite
	 */
	public function test_it_expires_values( Storage $storage ): void {
		$key    = 'uplink_test_key';
		$value  = 'expired';
		$expire = 1;

		$this->assertTrue( $storage->set( $key, $value, $expire ) );

		codecept_debug( 'Sleeping for 2 seconds...' );

		sleep( 2 );

		$this->assertNull( $storage->get( $key ) );
	}

}
