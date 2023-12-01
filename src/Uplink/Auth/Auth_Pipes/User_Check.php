<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Auth\Auth_Pipes;

use Closure;
use StellarWP\Uplink\Auth\Authorized;
use StellarWP\Uplink\Config;

final class User_Check {

	/**
	 * Ensure the user is logged in and is an admin.
	 *
	 * @param  Authorized $authorized
	 * @param  Closure  $next
	 *
	 * @return Authorized
	 */
	public function __invoke( Authorized $authorized, Closure $next ): Authorized {
		/**
		 * Filter the super admin user check.
		 *
		 * @param  bool  $is_super_admin  Whether the currently logged-in user is a super admin.
		 */
		$is_super_admin = (bool) apply_filters(
			'stellarwp/uplink/' . Config::get_hook_prefix() . '/auth/user_check',
			is_super_admin()
		);

		if ( ! $is_super_admin ) {
			$authorized->authorized = false;

			return $authorized;
		}

		return $next( $authorized );
	}

}
