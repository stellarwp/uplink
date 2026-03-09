<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Legacy;

use StellarWP\Uplink\Contracts\Abstract_Provider;

/**
 * Registers services for legacy license discovery.
 *
 * @since 3.0.0
 */
class Provider extends Abstract_Provider {

	/**
	 * @inheritDoc
	 */
	public function register(): void {
		$this->container->singleton( License_Repository::class, License_Repository::class );
	}
}
