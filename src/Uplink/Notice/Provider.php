<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Notice;

use StellarWP\Uplink\Contracts\Abstract_Provider;

final class Provider extends Abstract_Provider {

	/**
	 * @inheritDoc
	 */
	public function register(): void {
		$this->container->bind( Notice_Controller::class, Notice_Controller::class );
		$this->container->bind( Notice_Handler::class, static function ( $c ): Notice_Handler {
			return new Notice_Handler( $c->get( Notice_Controller::class ) );
		} );

		add_action( 'admin_notices', function (): void {
			$this->container->get( Notice_Handler::class )->display();
		}, 12, 0 );
	}

}
