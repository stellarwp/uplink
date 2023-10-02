<?php declare( strict_types=1 );

namespace StellarWP\Uplink\View;

use League\Plates\Engine;
use StellarWP\Uplink\Contracts\Abstract_Provider;

final class Provider extends Abstract_Provider {

	/**
	 * Configure the directory League Plates looks for view files.
	 *
	 * @link https://platesphp.com/
	 */
	public function register() {
		$this->container->singleton(
			Engine::class,
			new Engine( trailingslashit( __DIR__ . '/../../views' ) )
		);
	}
}
