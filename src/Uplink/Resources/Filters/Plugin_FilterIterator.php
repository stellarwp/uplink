<?php

namespace StellarWP\Uplink\Resources\Filters;

class Plugin_FilterIterator extends \FilterIterator implements \Countable {
	/**
	 * @inheritDoc
	 */
	public function accept(): bool {
		$resource = $this->getInnerIterator()->current();

		return 'plugin' === $resource->get_type();
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
