<?php declare( strict_types=1 );

namespace StellarWP\Uplink\License\Manager\Pipeline\Processors;

use Closure;
use StellarWP\Uplink\Config;
use StellarWP\Uplink\License\Manager\Pipeline\Traits\Multisite_Trait;
use Throwable;

final class Multisite_Domain_Mapping {

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
			if ( $this->is_unique_domain() ) {
				return Config::allows_network_domain_mapping_license();
			}
		} catch ( Throwable $e ) {
			return false;
		}

		return $next( $is_multisite_license );
	}
}
