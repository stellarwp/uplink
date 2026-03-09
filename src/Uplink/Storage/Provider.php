<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Storage;

use StellarWP\Uplink\Config;
use StellarWP\Uplink\Contracts\Abstract_Provider;
use StellarWP\Uplink\Storage\Contracts\Storage;
use StellarWP\Uplink\Storage\Drivers\Option_Storage;

final class Provider extends Abstract_Provider {

	/**
	 * @inheritDoc
	 */
	public function register() {
		$this->container->singleton(
			Option_Storage::class,
			function () {
				$option_name = Config::get_hook_prefix() . '_storage';

				return new Option_Storage( $option_name );
			} 
		);

		$this->container->singleton(
			Storage::class,
			static function ( $c ): Storage {
				return $c->get( Config::get_storage_driver() );
			} 
		);
	}
}
