<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features;

use ArrayAccess;
use ArrayIterator;
use Countable;
use Iterator;
use StellarWP\Uplink\Features\Types\Feature;

/**
 * A collection of Feature objects, keyed by slug.
 *
 * Mirrors the Resources\Collection pattern.
 *
 * @since TBD
 */
class Collection implements ArrayAccess, Iterator, Countable {

	/**
	 * Collection of features.
	 *
	 * @since TBD
	 *
	 * @var array<string, Feature>
	 */
	private array $features;

	/**
	 * The original Iterator, for memoization.
	 *
	 * @since TBD
	 *
	 * @var Iterator<string, Feature>|null
	 */
	private ?Iterator $iterator = null;

	/**
	 * Constructor for a collection of Feature objects.
	 *
	 * @since TBD
	 *
	 * @param Iterator<string, Feature>|array<string, Feature> $features An array or iterator of Features.
	 *
	 * @return void
	 */
	public function __construct( $features = [] ) {
		if ( $features instanceof Iterator ) {
			$this->iterator = $features;
			$features       = iterator_to_array( $features );
		}

		$this->features = $features;
	}

	/**
	 * Adds a feature to the collection.
	 *
	 * @since TBD
	 *
	 * @param Feature $feature Feature instance.
	 *
	 * @return Feature
	 */
	public function add( Feature $feature ): Feature {
		if ( ! $this->offsetExists( $feature->get_slug() ) ) {
			$this->offsetSet( $feature->get_slug(), $feature );
		}

		return $this->offsetGet( $feature->get_slug() );
	}

	/**
	 * @since TBD
	 *
	 * @return Feature
	 */
	#[\ReturnTypeWillChange]
	public function current(): Feature {
		return current( $this->features );
	}

	/**
	 * Alias of offsetGet().
	 *
	 * @since TBD
	 *
	 * @param string $offset The feature slug.
	 *
	 * @return Feature|null
	 */
	#[\ReturnTypeWillChange]
	public function get( $offset ): ?Feature {
		return $this->offsetGet( $offset );
	}

	/**
	 * @since TBD
	 *
	 * @return array-key|null
	 */
	#[\ReturnTypeWillChange]
	public function key() {
		return key( $this->features );
	}

	/**
	 * @inheritDoc
	 */
	public function next(): void {
		next( $this->features );
	}

	/**
	 * @inheritDoc
	 */
	public function offsetExists( $offset ): bool {
		return array_key_exists( $offset, $this->features );
	}

	/**
	 * @since TBD
	 *
	 * @return Feature|null
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet( $offset ): ?Feature {
		return $this->features[ $offset ] ?? null;
	}

	/**
	 * @inheritDoc
	 */
	public function offsetSet( $offset, $value ): void {
		$this->features[ $offset ] = $value;
	}

	/**
	 * @inheritDoc
	 */
	public function offsetUnset( $offset ): void {
		unset( $this->features[ $offset ] );
	}

	/**
	 * Remove a feature from the collection.
	 *
	 * @since TBD
	 *
	 * @param string $slug Feature slug.
	 *
	 * @return void
	 */
	public function remove( string $slug ): void {
		$this->offsetUnset( $slug );
	}

	/**
	 * @inheritDoc
	 */
	public function rewind(): void {
		reset( $this->features );
	}

	/**
	 * @inheritDoc
	 */
	public function valid(): bool {
		return key( $this->features ) !== null;
	}

	/**
	 * @inheritDoc
	 */
	public function count(): int {
		return count( $this->features );
	}

	/**
	 * Returns a clone of the underlying iterator.
	 *
	 * @since TBD
	 *
	 * @return Iterator<string, Feature>
	 */
	public function getIterator(): Iterator {
		if ( isset( $this->iterator ) ) {
			return $this->iterator;
		}

		return $this->iterator = new ArrayIterator( $this->features );
	}
}
