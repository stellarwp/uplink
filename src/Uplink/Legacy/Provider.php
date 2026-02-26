<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Legacy;

use StellarWP\Uplink\Contracts\Abstract_Provider;
use StellarWP\Uplink\Resources\Collection;

/**
 * Registers hooks for legacy suppression.
 *
 * Each Uplink instance handles its own resources independently
 * since each instance only knows about the resources registered
 * in its own collection.
 *
 * @since 3.1.0
 */
class Provider extends Abstract_Provider {

	/**
	 * @inheritDoc
	 */
	public function register(): void {
		$this->container->singleton(
			LegacyManager::class,
			function () {
				return new LegacyManager(
					$this->container->get( Collection::class )
				);
			}
		);

		add_action( 'admin_init', [ $this, 'handle_legacy' ], 1, 0 );
	}

	/**
	 * Suppress legacy hooks when a unified key is present.
	 *
	 * @since 3.1.0
	 *
	 * @return void
	 */
	public function handle_legacy(): void {
		$this->container->get( LegacyManager::class )->suppress_all();
	}
}
