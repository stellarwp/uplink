<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Traits;

trait With_Debugging {

	protected function is_wp_debug(): bool {
		return defined( 'WP_DEBUG' ) && WP_DEBUG;
	}

}
