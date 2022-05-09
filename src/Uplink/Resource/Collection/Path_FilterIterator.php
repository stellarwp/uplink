<?php

namespace StellarWP\Uplink\Resource\Collection;

class Path_FilterIterator extends \FilterIterator {
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
}
