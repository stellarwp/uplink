<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Legacy;

use StellarWP\Uplink\Contracts\Abstract_Provider;
use StellarWP\Uplink\Resources\Collection;

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
		$this->container->singleton(
			Legacy_Manager::class,
			function () {
				return new Legacy_Manager(
					$this->container->get( Collection::class )
				);
			}
		);

		$this->container->singleton( License_Repository::class, License_Repository::class );
	}
}
