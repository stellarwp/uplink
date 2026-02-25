<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Utils;

use ArrayAccess;
use ArrayIterator;
use Countable;
use Iterator;

/**
 * A generic keyed collection.
 *
 * @since 3.0.0
 */
class Collection implements ArrayAccess, Iterator, Countable {

	/**
	 * The collection items.
	 *
	 * @since 3.0.0
	 *
	 * @var array<string, mixed>
	 */
	protected $items;

	/**
	 * The original Iterator, for memoization.
	 *
	 * @since 3.0.0
	 *
	 * @var Iterator<string, mixed>|null
	 */
	private $iterator = null;

	/**
	 * Constructor for a keyed collection.
	 *
	 * @since 3.0.0
	 *
	 * @param Iterator<string, mixed>|array<string, mixed> $items An array or iterator of items.
	 *
	 * @return void
	 */
	public function __construct( $items = [] ) {
		if ( $items instanceof Iterator ) {
			$this->iterator = $items;
			$items          = iterator_to_array( $items );
		}

		$this->items = $items;
	}

	/**
	 * @since 3.0.0
	 *
	 * @return mixed
	 */
	#[\ReturnTypeWillChange]
	public function current() {
		return current( $this->items );
	}

	/**
	 * Retrieves an item by key.
	 *
	 * @since 3.0.0
	 *
	 * @param string $offset The item key.
	 *
	 * @return mixed|null
	 */
	#[\ReturnTypeWillChange]
	public function get( $offset ) {
		return $this->offsetGet( $offset );
	}

	/**
	 * @since 3.0.0
	 *
	 * @return array-key|null
	 */
	#[\ReturnTypeWillChange]
	public function key() {
		return key( $this->items );
	}

	/**
	 * @inheritDoc
	 */
	public function next(): void {
		next( $this->items );
	}

	/**
	 * @inheritDoc
	 */
	public function offsetExists( $offset ): bool {
		return array_key_exists( $offset, $this->items );
	}

	/**
	 * @since 3.0.0
	 *
	 * @return mixed|null
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet( $offset ) {
		return $this->items[ $offset ] ?? null;
	}

	/**
	 * @inheritDoc
	 */
	public function offsetSet( $offset, $value ): void {
		$this->items[ $offset ] = $value;
	}

	/**
	 * @inheritDoc
	 */
	public function offsetUnset( $offset ): void {
		unset( $this->items[ $offset ] );
	}

	/**
	 * Removes an item from the collection.
	 *
	 * @since 3.0.0
	 *
	 * @param string $key The item key.
	 *
	 * @return void
	 */
	public function remove( string $key ): void {
		$this->offsetUnset( $key );
	}

	/**
	 * @inheritDoc
	 */
	public function rewind(): void {
		reset( $this->items );
	}

	/**
	 * @inheritDoc
	 */
	public function valid(): bool {
		return key( $this->items ) !== null;
	}

	/**
	 * @inheritDoc
	 */
	public function count(): int {
		return count( $this->items );
	}

	/**
	 * Returns a clone of the underlying iterator.
	 *
	 * @since 3.0.0
	 *
	 * @return Iterator<string, mixed>
	 */
	public function getIterator(): Iterator {
		if ( isset( $this->iterator ) ) {
			return $this->iterator;
		}

		return $this->iterator = new ArrayIterator( $this->items );
	}
}
