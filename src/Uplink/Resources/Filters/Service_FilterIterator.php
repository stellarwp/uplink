<?php

namespace StellarWP\Uplink\Resources\Filters;

class Service_FilterIterator extends \FilterIterator implements \Countable {
	/**
	 * @inheritDoc
	 */
	public function accept(): bool {
		$resource = $this->getInnerIterator()->current();

		return 'service' === $resource->get_type();
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
