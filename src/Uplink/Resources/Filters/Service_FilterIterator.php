<?php

namespace StellarWP\Uplink\Resources\Filters;

class Service_FilterIterator extends \FilterIterator {
	/**
	 * @inheritDoc
	 */
	public function accept(): bool {
		$resource = $this->getInnerIterator()->current();

		return 'service' === $resource->get_type();
	}
}
