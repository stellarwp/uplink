<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Auth;

use StellarWP\Uplink\Auth\Token\Token_Manager_Factory;
use StellarWP\Uplink\Config;
use StellarWP\Uplink\Contracts\Abstract_Provider;

final class Provider extends Abstract_Provider {

	/**
	 * @inheritDoc
	 */
	public function register() {
		if ( ! $this->container->has( Config::TOKEN_OPTION_NAME ) ) {
			return;
		}

		$this->container->bind(
			Token_Manager_Factory::class,
			new Token_Manager_Factory( $this->container->get( Config::TOKEN_OPTION_NAME ) )
		);
	}

}
