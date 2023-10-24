<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Auth\Auth_Pipes;

use Closure;
use StellarWP\Uplink\Config;

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
			return false;
		}

		return $next( $can_auth );
	}

}
