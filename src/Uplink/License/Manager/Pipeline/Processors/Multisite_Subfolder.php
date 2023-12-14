<?php declare( strict_types=1 );

namespace StellarWP\Uplink\License\Manager\Pipeline\Processors;

use Closure;
use StellarWP\Uplink\Config;
use StellarWP\Uplink\License\Manager\Pipeline\Traits\Multisite_Trait;

final class Multisite_Subfolder {

	use Multisite_Trait;

	/**
	 * Check if we're using multisite subfolders and if that type of network license is allowed.
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

		if ( $this->is_subfolder_install() ) {
			return Config::supports_site_level_licenses_for_subfolder_multisite();
		}

		return $next( $is_multisite_license );
	}

}
