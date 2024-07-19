<?php declare( strict_types=1 );

namespace StellarWP\Uplink\View;

use StellarWP\Uplink\Contracts\Abstract_Provider;
use StellarWP\Uplink\View\Contracts\View;

final class Provider extends Abstract_Provider {

	/**
	 * Configure the View Renderer.
	 */
	public function register() {
		$this->container->singleton(
			WordPress_View::class,
			new WordPress_View( __DIR__ . '/../../views', '.php' )
		);

		$this->container->bind( View::class, $this->container->get( WordPress_View::class ) );
	}
}
