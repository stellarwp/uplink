<?php

namespace StellarWP\Uplink\Resources;

use ArrayAccess;
use Iterator;

class Collection implements ArrayAccess, Iterator {

	/**
	 * Collection of resources.
	 *
	 * @var array<string, Resource>
	 */
	private $resources = [];

	/**
	 * Adds a resource to the collection.
	 *
	 * @since 1.0.0
	 *
	 * @param Resource $resource Resource instance.
	 *
	 * @return Resource
	 */
	public function add( Resource $resource ) {
		$this->offsetSet( $resource->get_slug(), $resource );

		return $this->offsetGet( $resource->get_slug() );
	}

	/**
	 * @return Resource|bool
	 */
	#[\ReturnTypeWillChange]
	public function current() {
		return ( ! $this->resources ) ? current( $this->resources ) : false;
	}

	/**
	 * Gets the resource with the given path.
	 *
	 * @since 1.0.0
	 *
	 * @param string $path Path to filter collection by.
	 * @param Iterator $iterator Optional. Iterator to filter.
	 *
	 * @return Filters\Path_FilterIterator
	 */
	public function get_by_path( string $path, $iterator = null ) {
		return new Filters\Path_FilterIterator( $iterator ?: $this, [ $path ] );
	}

	/**
	 * Gets the resource with the given paths.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string> $paths Paths to filter collection by.
	 * @param Iterator $iterator Optional. Iterator to filter.
	 *
	 * @return Filters\Path_FilterIterator
	 */
	public function get_by_paths( array $paths, $iterator = null ) {
		return new Filters\Path_FilterIterator( $iterator ?: $this, $paths );
	}

	/**
	 * Gets the plugin resources.
	 *
	 * @since 1.0.0
	 *
	 * @param Iterator $iterator Optional. Iterator to filter.
	 *
	 * @return Filters\Plugin_FilterIterator
	 */
	public function get_plugins( $iterator = null ) {
		return new Filters\Plugin_FilterIterator( $iterator ?: $this );
	}

	/**
	 * Gets the service resources.
	 *
	 * @since 1.0.0
	 *
	 * @param Iterator $iterator Optional. Iterator to filter.
	 *
	 * @return Filters\Service_FilterIterator
	 */
	public function get_services( $iterator = null ) {
		return new Filters\Service_FilterIterator( $iterator ?: $this );
	}

	/**
	 * @return array-key|null
	 */
	#[\ReturnTypeWillChange]
	public function key() {
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
	 * @return Resource|null
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet( $offset ): ?Resource {
		return $this->resources[ $offset ] ?? null;
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
	 *
	 * @return void
	 */
	public function remove( $slug ) {
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
	 * @param Resource $resource Resource instance.
	 *
	 * @return mixed
	 */
	public function set( $slug, Resource $resource ) {
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
