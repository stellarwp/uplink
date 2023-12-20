<?php declare( strict_types=1 );

namespace StellarWP\Uplink\License\Manager\Pipeline\Processors;

use Closure;

final class Multisite_Network_Admin {

	/**
	 * If our form is rendered in the network admin, always get and store license keys in the network.
	 *
	 * @note is_multisite() is already checked.
	 *
	 * @param  bool $is_multisite_license
	 * @param  Closure  $next
	 *
	 * @return bool
	 */
	public function __invoke( bool $is_multisite_license, Closure $next ): bool {
		if ( is_network_admin() ) {
			return true;
		}

		return $next( $is_multisite_license );
	}

}
