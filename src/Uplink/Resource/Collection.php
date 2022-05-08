<?php

namespace StellarWP\Uplink\Resource;

class Collection implements \ArrayAccess, \Iterator {
	/**
	 * Collection of resources.
	 *
	 * @var array<mixed>
	 */
	private $resources = [];

	/**
	 * Adds a resource to the collection.
	 *
	 * @since 1.0.0
	 *
	 * @param Resource_Abstract $resource Resource instance.
	 *
	 * @return mixed
	 */
	public function add( Resource_Abstract $resource ) {
		$this->offsetSet( $resource->get_slug(), $resource );

		return $this->offsetGet( $resource->get_slug() );
	}

	/**
	 * @inheritDoc
	 */
	public function current() {
		return current( $this->resources );
	}

	/**
	 * @inheritDoc
	 */
	public function key(): mixed {
		return key( $this->resources );
	}

	/**
	 * @inheritDoc
	 */
	public function next(): void {
		next( $this->resources );
	}

	/**
	 * @inheritDoc
	 */
	public function offsetExists( $offset ): bool {
		return isset( $this->resources[ $offset ] );
	}

	/**
	 * @inheritDoc
	 */
	public function offsetGet( $offset ) {
		return $this->resources[ $offset ];
	}

	/**
	 * @inheritDoc
	 */
	public function offsetSet( $offset, $value ): void {
		$this->resources[ $offset ] = $value;
	}

	/**
	 * @inheritDoc
	 */
	public function offsetUnset( $offset ): void {
		unset( $this->resources[ $offset ] );
	}

	/**
	 * Helper function for removing a resource from the collection.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Resource slug.
	 */
	public function remove( $slug ): void {
		$this->offsetUnset( $slug );
	}

	/**
	 * @inheritDoc
	 */
	public function rewind(): void {
		reset( $this->resources );
	}

	/**
	 * Sets a resource in the collection.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Resource slug.
	 * @param Resource_Abstract $resource Resource instance.
	 *
	 * @return mixed
	 */
	public function set( $slug, Resource_Abstract $resource ) {
		$this->offsetSet( $slug, $resource );

		return $this->offsetGet( $slug );
	}

	/**
	 * @inheritDoc
	 */
	public function valid(): bool {
		return key( $this->resources ) !== null;
	}
}