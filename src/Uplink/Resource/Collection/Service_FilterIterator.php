<?php

namespace StellarWP\Uplink\Resource\Collection;

class Service_FilterIterator extends \FilterIterator {
	/**
	 * @inheritDoc
	 */
	public function accept(): bool {
		$resource = $this->getInnerIterator()->current();

		return 'service' === $resource->get_type();
	}
}
