<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Notice;

use StellarWP\Uplink\Contracts\Abstract_Provider;

final class Provider extends Abstract_Provider {

	/**
	 * @inheritDoc
	 */
	public function register(): void {
		add_action( 'admin_notices', function(): void {
			$this->container->get( Notice_Handler::class )->display();
		}, 12, 0 );
	}

}
