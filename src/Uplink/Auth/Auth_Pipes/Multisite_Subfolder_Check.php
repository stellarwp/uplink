<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Auth\Auth_Pipes;

use Closure;
use StellarWP\Uplink\Utils\Checks;

final class Multisite_Subfolder_Check {

	/**
	 * Prevent authorization if on a sub-site with multisite sub-folders enabled.
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

		$id = get_main_site_id();

		if ( $id <= 0 ) {
			return $next( $can_auth );
		}

		$current_site_url = get_site_url();
		$main_site_url    = get_site_url( $id );

		// The current sites with the main site URL, so we're in subfolder mode.
		if ( Checks::str_starts_with( $current_site_url, $main_site_url ) ) {
			return false;
		}

		return $next( $can_auth );
	}

}
