<?php declare( strict_types=1 );

namespace StellarWP\Uplink\License\Manager\Pipeline\Processors;

use Closure;
use StellarWP\Uplink\Config;
use StellarWP\Uplink\License\Manager\Pipeline\Traits\Multisite_Trait;
use Throwable;

final class Multisite_Subdomain {

	use Multisite_Trait;

	/**
	 * Checks if a sub-site already has a network token.
	 *
	 * @param  bool $is_multisite_license
	 * @param  Closure  $next
	 *
	 * @return bool
	 */
	public function __invoke( bool $is_multisite_license, Closure $next ): bool {
		if ( is_main_site() ) {
			return $next( $is_multisite_license );
		}

		try {
			if ( $this->is_subdomain() ) {
				return Config::supports_site_level_licenses_for_subdomain_multisite();
			}
		} catch ( Throwable $e ) {
			return false;
		}

		return $next( $is_multisite_license );
	}

}
