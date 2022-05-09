<?php

namespace StellarWP\Uplink\Resource\Collection;

class Plugin_FilterIterator extends \FilterIterator {
	/**
	 * @inheritDoc
	 */
	public function accept(): bool {
		$resource = $this->getInnerIterator()->current();

		return 'plugin' === $resource->get_type();
	}
}
