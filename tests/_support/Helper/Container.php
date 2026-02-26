<?php

namespace StellarWP\Uplink\Tests;

use StellarWP\ContainerContract\ContainerInterface;
use lucatume\DI52\Container as DI52Container;

class Container extends DI52Container implements ContainerInterface {

	/**
	 * Alias for get().
	 *
	 * @param string $id Identifier of the entry to look for.
	 *
	 * @return mixed
	 */
	public function make( $id ) {
		return $this->get( $id );
	}
}
