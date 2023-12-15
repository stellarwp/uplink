<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Components\Settings\Traits;

trait Get_Value_Trait {

	/**
	 * Fetches a component's value from site options if this component
	 * is rendered inside the WordPress network admin.
	 *
	 * @param  string  $name The option name.
	 * @param  mixed   $default The default value to return if not found.
	 *
	 * @return mixed
	 */
	protected function get_value( string $name, $default = false ) {
		return is_network_admin() ? get_site_option( $name, $default ) : get_option( $name, $default );
	}

}
