<?php declare( strict_types=1 );

namespace StellarWP\Uplink\API\Functions;

use StellarWP\Uplink\Contracts\Abstract_Provider;
use StellarWP\Uplink\Uplink;

/**
 * Registers global (non-namespaced) Uplink helper functions.
 *
 * @since 3.0.0
 */
final class Provider extends Abstract_Provider {

	/**
	 * @inheritDoc
	 */
	public function register(): void {
		Global_Function_Registry::register( $this->container, Uplink::VERSION );
	}
}
