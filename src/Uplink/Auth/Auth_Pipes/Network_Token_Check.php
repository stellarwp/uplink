<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Auth\Auth_Pipes;

use Closure;
use StellarWP\Uplink\Auth\Authorized;
use StellarWP\Uplink\Auth\Token\Managers\Network_Token_Manager;

final class Network_Token_Check {

	/**
	 * @var Network_Token_Manager
	 */
	private $token_manager;

	/**
	 * @param  Network_Token_Manager  $token_manager The Token Manager Factory.
	 */
	public function __construct( Network_Token_Manager $token_manager ) {
		$this->token_manager = $token_manager;
	}

	/**
	 * Checks if a sub-site already has a network token.
	 *
	 * @param  Authorized  $authorized
	 * @param  Closure  $next
	 *
	 * @return Authorized
	 */
	public function __invoke( Authorized $authorized, Closure $next ): Authorized {
		if ( ! is_multisite() ) {
			return $next( $authorized );
		}

		if ( is_main_site() ) {
			return $next( $authorized );
		}

		// Token already exists at the network level, don't authorize for this sub-site.
		if ( $authorized->resource()->is_network_activated() && $this->token_manager->get() ) {
			$authorized->authorized = false;

			return $authorized;
		}

		return $next( $authorized );
	}

}
