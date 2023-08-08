<?php

namespace StellarWP\Uplink\Resources\Filters;

class Path_FilterIterator extends \FilterIterator implements \Countable {
	/**
	 * Paths to filter.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string>
	 */
	private $paths = [];

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param \Iterator $iterator Iterator to filter.
	 * @param array<string> $paths Paths to filter.
	 */
	public function __construct( \Iterator $iterator, array $paths ) {
		parent::__construct( $iterator );

		$this->paths = $paths;
	}

	/**
	 * @inheritDoc
	 */
	public function accept(): bool {
		$resource = $this->getInnerIterator()->current();

		return in_array( $resource->get_path(), $this->paths, true );
	}

	/**
	 * @inheritDoc
	 */
	public function count() : int {
		$count = 0;
		foreach ( $this as $item ) {
			$count++;
		}

		return $count;
	}
}
