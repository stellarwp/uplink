<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Auth\Auth_Pipes;

use Closure;
use StellarWP\Uplink\Auth\Token\Contracts\Token_Manager;

final class Network_Token_Check {

	/**
	 * @var Token_Manager
	 */
	private $token_manager;

	/**
	 * @param  Token_Manager  $token_manager The Token Manager.
	 */
	public function __construct( Token_Manager $token_manager ) {
		$this->token_manager = $token_manager;
	}

	/**
	 * Checks if a sub-site already has a network token.
	 *
	 * @param  bool  $can_auth
	 * @param  Closure  $next
	 *
	 * @return bool
	 */
	public function __invoke( bool $can_auth, Closure $next ): bool {
		if ( ! is_multisite() ) {
			return $next( $can_auth );
		}

		if ( is_main_site() ) {
			return $next( $can_auth );
		}

		// Token already exists at the network level, don't authorize for this sub-site.
		if ( $this->token_manager->get() ) {
			return false;
		}

		return $next( $can_auth );
	}

}
