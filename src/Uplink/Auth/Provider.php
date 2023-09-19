<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Auth;

use StellarWP\Uplink\Auth\Auth_Pipes\Multisite_Subfolder_Check;
use StellarWP\Uplink\Auth\Auth_Pipes\Network_Token_Check;
use StellarWP\Uplink\Auth\Auth_Pipes\User_Check;
use StellarWP\Uplink\Auth\Token\Contracts\Token_Manager;
use StellarWP\Uplink\Config;
use StellarWP\Uplink\Contracts\Abstract_Provider;
use StellarWP\Uplink\Pipeline\Pipeline;

final class Provider extends Abstract_Provider {

	/**
	 * @inheritDoc
	 */
	public function register() {
		if ( ! $this->container->has( Config::TOKEN_OPTION_NAME ) ) {
			return;
		}

		$this->container->bind(
			Token_Manager::class,
			static function ( $c ) {
				return new Token\Token_Manager( $c->get( Config::TOKEN_OPTION_NAME ) );
			}
		);

		$this->register_authorizer();
	}

	/**
	 * Registers the Authorizer and the steps in order for the pipeline
	 * processing.
	 */
	private function register_authorizer(): void {
		$this->container->singleton(
			Network_Token_Check::class,
			static function ( $c ) {
				return new Network_Token_Check( $c->get( Token_Manager::class ) );
			}
		);

		$pipeline = ( new Pipeline( $this->container ) )->through( [
			User_Check::class,
			Multisite_Subfolder_Check::class,
			Network_Token_Check::class,
		] );

		$this->container->singleton(
			Authorizer::class,
			static function () use ( $pipeline ) {
				return new Authorizer( $pipeline );
			}
		);
	}

}
