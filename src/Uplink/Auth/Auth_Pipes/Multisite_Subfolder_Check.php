<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Auth\Auth_Pipes;

use Closure;
use StellarWP\Uplink\Auth\Authorized;
use StellarWP\Uplink\Utils\Checks;

final class Multisite_Subfolder_Check {

	/**
	 * Prevent authorization if on a sub-site with multisite sub-folders enabled and
	 * the plugin is network activated.
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

		$id = get_main_site_id();

		if ( $id <= 0 ) {
			return $next( $authorized );
		}

		$current_site_url = get_site_url();
		$main_site_url    = get_site_url( $id );

		// The current sites with the main site URL, so we're in subfolder mode.
		if ( Checks::str_starts_with( $current_site_url, $main_site_url ) && $authorized->resource->is_network_activated() ) {
			$authorized->authorized = false;

			return $authorized;
		}

		return $next( $authorized );
	}

}
