<?php declare( strict_types=1 );

namespace StellarWP\Uplink\License\Manager\Pipeline\Processors;

use Closure;
use StellarWP\Uplink\Config;

final class Multisite_Main_Site {

	/**
	 * If we're on the main site any of the multisite licensing options are enabled,
	 * licensing is enabled.
	 *
	 * @param  bool  $is_multisite_license
	 * @param  Closure  $next
	 *
	 * @throws \RuntimeException
	 *
	 * @return bool
	 */
	public function __invoke( bool $is_multisite_license, Closure $next ): bool {
		if ( is_main_site() && Config::supports_network_licenses() ) {
			$is_multisite_license = true;
		}

		return $next( $is_multisite_license );
	}

}
