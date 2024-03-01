<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Auth;

use StellarWP\Uplink\Config;

/**
 * Determines if the current site will allow the user to use the authorize button.
 */
final class Authorizer {

	/**
	 * Checks if the current user can perform an action.
	 *
	 * @throws \RuntimeException
	 *
	 * @return bool
	 */
	public function can_auth(): bool {
		return (bool) apply_filters(
			'stellarwp/uplink/' . Config::get_hook_prefix() . '/auth/user_check',
			is_super_admin()
		);
	}

}
