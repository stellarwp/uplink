<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Auth\Auth_Pipes;

use Closure;

final class User_Check {

	/**
	 * Ensure the user is logged in and is an admin.
	 *
	 * @param  bool  $can_auth
	 * @param  Closure  $next
	 *
	 * @return bool
	 */
	public function __invoke( bool $can_auth, Closure $next ): bool {
		if ( ! is_super_admin() ) {
			return false;
		}

		return $next( $can_auth );
	}

}
