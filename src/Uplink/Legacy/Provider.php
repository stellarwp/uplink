<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Legacy;

use StellarWP\Uplink\Contracts\Abstract_Provider;
use StellarWP\Uplink\Legacy\Admin\License_Notice_Handler;
use StellarWP\Uplink\Notice\Notice_Controller;

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

		$this->container->singleton(
			License_Notice_Handler::class,
			static function ( $c ): License_Notice_Handler {
				return new License_Notice_Handler(
					$c->get( License_Repository::class ),
					$c->get( Notice_Controller::class )
				);
			}
		);

		add_action( 'admin_notices', [ $this->container->get( License_Notice_Handler::class ), 'display' ], 10, 0 );
	}
}
