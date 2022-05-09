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
	 * Gets the resource with the given path.
	 *
	 * @since 1.0.0
	 *
	 * @param string $path Path to filter collection by.
	 * @param \Iterator $iterator Optional. Iterator to filter.
	 *
	 * @return Collection\Path_FilterIterator
	 */
	public function get_by_path( string $path, $iterator = null ) {
		return new Collection\Path_FilterIterator( $iterator ?: $this, [ $path ] );
	}

	/**
	 * Gets the resource with the given paths.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string> $paths Paths to filter collection by.
	 * @param \Iterator $iterator Optional. Iterator to filter.
	 *
	 * @return Collection\Path_FilterIterator
	 */
	public function get_by_paths( array $paths, $iterator = null ) {
		return new Collection\Path_FilterIterator( $iterator ?: $this, $paths );
	}

	/**
	 * Gets the plugin resources.
	 *
	 * @since 1.0.0
	 *
	 * @param \Iterator $iterator Optional. Iterator to filter.
	 *
	 * @return Collection\Plugin_FilterIterator
	 */
	public function get_plugins( $iterator = null ) {
		return new Collection\Plugin_FilterIterator( $iterator ?: $this );
	}

	/**
	 * Gets the service resources.
	 *
	 * @since 1.0.0
	 *
	 * @param \Iterator $iterator Optional. Iterator to filter.
	 *
	 * @return Collection\Service_FilterIterator
	 */
	public function get_services( $iterator = null ) {
		return new Collection\Service_FilterIterator( $iterator ?: $this );
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
