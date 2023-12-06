<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Auth;

use StellarWP\Uplink\Auth\Admin\Connect_Controller;
use StellarWP\Uplink\Auth\Admin\Disconnect_Controller;
use StellarWP\Uplink\Auth\License\License_Manager;
use StellarWP\Uplink\Auth\License\Pipeline\Processors\Multisite_Domain_Mapping;
use StellarWP\Uplink\Auth\License\Pipeline\Processors\Multisite_Subdomain;
use StellarWP\Uplink\Auth\License\Pipeline\Processors\Multisite_Subfolder;
use StellarWP\Uplink\Auth\License\Pipeline\Processors\Multisite_Token;
use StellarWP\Uplink\Auth\Token\Managers\Network_Token_Manager;
use StellarWP\Uplink\Auth\Token\Managers\Token_Manager;
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

		$this->container->singleton(
			Token_Manager::class,
			static function ( $c ) {
				return new Token_Manager( $c->get( Config::TOKEN_OPTION_NAME ) );
			}
		);

		$this->container->singleton(
			Network_Token_Manager::class,
			static function ( $c ) {
				return new Network_Token_Manager( $c->get( Config::TOKEN_OPTION_NAME ) );
			}
		);

		$this->register_nonce();
		$this->register_license_manager();
		$this->register_auth_disconnect();
		$this->register_auth_connect();
	}

	/**
	 * Register nonce container definitions.
	 *
	 * @return void
	 */
	private function register_nonce(): void {
		/**
		 * Filter how long the callback nonce is valid for.
		 *
		 * @note There is also an expiration time in the Uplink Origin plugin.
		 *
		 * Default: 35 minutes, to allow time for them to properly log in.
		 *
		 * @param int $expiration Nonce expiration time in seconds.
		 */
		$expiration = apply_filters( 'stellarwp/uplink/' . Config::get_hook_prefix() . '/auth/nonce_expiration', 2100 );
		$expiration = absint( $expiration );

		$this->container->singleton( Nonce::class, new Nonce( $expiration ) );
	}

	/**
	 * Register the license manager and its pipeline to detect different
	 * mulitsite licenses.
	 *
	 * @return void
	 */
	private function register_license_manager(): void {
		$pipeline = ( new Pipeline( $this->container ) )->through( [
			Multisite_Subfolder::class,
			Multisite_Subdomain::class,
			Multisite_Domain_Mapping::class,
			Multisite_Token::class,
		] );

		$this->container->singleton(
			License_Manager::class,
			static function () use ( $pipeline ) {
				return new License_Manager( $pipeline );
			}
		);
	}

	/**
	 * Register auth disconnection definitions and hooks.
	 *
	 * @return void
	 */
	private function register_auth_disconnect(): void {
		$this->container->singleton( Disconnect_Controller::class, Disconnect_Controller::class );

		add_action( 'admin_init', [ $this->container->get( Disconnect_Controller::class ), 'maybe_disconnect' ], 9, 0 );
	}

	/**
	 * Register auth connection definitions and hooks.
	 *
	 * @return void
	 */
	private function register_auth_connect(): void {
		$this->container->singleton( Connect_Controller::class, Connect_Controller::class );

		add_action( 'admin_init', [ $this->container->get( Connect_Controller::class ), 'maybe_store_token_data'], 9, 0 );
	}

}
