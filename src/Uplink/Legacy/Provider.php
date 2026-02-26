<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Legacy;

use StellarWP\Uplink\Contracts\Abstract_Provider;
use StellarWP\Uplink\Resources\Collection;
use StellarWP\Uplink\Utils\Version;
/**
 * Registers hooks for legacy suppression.
 *
 * The highest Uplink version (the leader) fires a cross-instance
 * action so every instance can evaluate its own suppress_when
 * callbacks.
 *
 * @since 3.0.0
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

		$this->container->singleton( LicenseRepository::class, LicenseRepository::class );

		add_action( 'admin_init', [ $this, 'handle_legacy' ], 1, 0 );
	}

	/**
	 * When this instance is the leader, fire the cross-instance
	 * suppression action. Each plugin's suppress_when callback
	 * decides whether suppression actually runs.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function handle_legacy(): void {
		if ( ! Version::should_handle( 'legacy_suppression' ) ) {
			return;
		}

		do_action( 'stellarwp/uplink/suppress_legacy' );
	}
}
