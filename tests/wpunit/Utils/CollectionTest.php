<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Utils;

use ArrayIterator;
use StellarWP\Uplink\Tests\UplinkTestCase;
use StellarWP\Uplink\Utils\Collection;

final class CollectionTest extends UplinkTestCase {

	/**
	 * Tests that items can be set and retrieved by key.
	 *
	 * @return void
	 */
	public function test_it_sets_and_gets_items(): void {
		$collection = new Collection();

		$collection['key-a'] = 'value-a';
		$collection['key-b'] = 'value-b';

		$this->assertSame( 2, $collection->count() );
		$this->assertSame( 'value-a', $collection->get( 'key-a' ) );
		$this->assertSame( 'value-b', $collection->get( 'key-b' ) );
	}

	/**
	 * Tests that null is returned when retrieving a key that does not exist.
	 *
	 * @return void
	 */
	public function test_it_returns_null_for_unknown_key(): void {
		$collection = new Collection();

		$this->assertNull( $collection->get( 'nonexistent' ) );
	}

	/**
	 * Tests an item can be removed by key.
	 *
	 * @return void
	 */
	public function test_it_removes_items(): void {
		$collection = new Collection( [ 'key-a' => 'value-a' ] );

		$this->assertSame( 1, $collection->count() );

		$collection->remove( 'key-a' );

		$this->assertSame( 0, $collection->count() );
		$this->assertNull( $collection->get( 'key-a' ) );
	}

	/**
	 * Tests that the collection implements ArrayAccess for isset and offset retrieval.
	 *
	 * @return void
	 */
	public function test_it_supports_array_access(): void {
		$collection = new Collection( [ 'key-a' => 'value-a' ] );

		$this->assertTrue( isset( $collection['key-a'] ) );
		$this->assertFalse( isset( $collection['nonexistent'] ) );
		$this->assertSame( 'value-a', $collection['key-a'] );
	}

	/**
	 * Tests that the collection can be iterated with foreach.
	 *
	 * @return void
	 */
	public function test_it_iterates_over_items(): void {
		$collection = new Collection(
			[
				'key-a' => 'value-a',
				'key-b' => 'value-b',
			] 
		);

		$keys = [];

		foreach ( $collection as $key => $value ) {
			$keys[] = $key;
		}

		$this->assertSame( [ 'key-a', 'key-b' ], $keys );
	}

	/**
	 * Tests that the collection can be constructed from an ArrayIterator.
	 *
	 * @return void
	 */
	public function test_it_accepts_an_iterator(): void {
		$items = [
			'key-a' => 'value-a',
			'key-b' => 'value-b',
		];

		$collection = new Collection( new ArrayIterator( $items ) );

		$this->assertSame( 2, $collection->count() );
		$this->assertSame( 'value-a', $collection->get( 'key-a' ) );
		$this->assertSame( 'value-b', $collection->get( 'key-b' ) );
	}

	/**
	 * Tests that the collection can be constructed from a plain array.
	 *
	 * @return void
	 */
	public function test_it_accepts_an_array(): void {
		$collection = new Collection(
			[
				'key-a' => 'value-a',
				'key-b' => 'value-b',
			] 
		);

		$this->assertSame( 2, $collection->count() );
	}
}
