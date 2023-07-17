<?php declare(strict_types=1);

namespace StellarWP\Uplink\Admin;

use StellarWP\Uplink\Config;
use StellarWP\Uplink\Resources\Collection;
use StellarWP\Uplink\Utils\Namespaces;

class Auth {

	public function do_auth_html() {
		$collection = Config::get_container()->get( Collection::class );
		$plugin     = $collection->current();
		$license    = $plugin->get_license_object();

		$token   = get_option( sprintf( '%s%s_auth_token', Namespaces::get_option_name( 'origin', '%TEXTDOMAIN%' ), $license->origin->slug ?? '' ), '' );
		$message = esc_html__( 'Connect to receive updates', '%TEXTDOMAIN%' );
		$classes = [ 'button', 'button-primary' ];
		$url     = Namespaces::get_hook_name( 'connect', '%TEXTDOMAIN%' );

		if ( ! empty( $token ) ) {
			$message = esc_html__( 'Disconnect', '%TEXTDOMAIN%' );
			$classes = [ 'button', 'button-secondary'];
			$url     = Namespaces::get_hook_name( 'disconnect', '%TEXTDOMAIN%' );
		}

		$btn_html = sprintf(
			'<a href="%s" class="%s">%s</a>',
			$url,
			implode( ' ', $classes ),
			$message
		);

		return apply_filters( Namespaces::get_hook_name( 'connect/btn/html', '%TEXTDOMAIN%' ), $btn_html, $url, $classes );
	}

}
