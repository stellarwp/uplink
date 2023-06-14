<?php declare(strict_types=1);

namespace StellarWP\Uplink\Admin;

use StellarWP\Uplink\Config;
use StellarWP\Uplink\Resources\Collection;

class Auth {

	public function do_auth_html() {
		$collection = Config::get_container()->get( Collection::class );
		$plugin     = $collection->current();
		$license    = $plugin->get_license_object();

		$token   = get_option( sprintf( 'stellarwp_origin_%s_auth_token', $license->origin->slug ?? '' ), '' );
		$message = esc_html__( 'Connect to origin', '%TEXTDOMAIN%' );
		$classes = [ 'button', 'button-primary' ];
		$url     = '/stellarwp/connect';

		if ( ! empty( $token ) ) {
			$message = esc_html__( 'Disconnect from origin', '%TEXTDOMAIN%' );
			$classes = [ 'button', 'button-secondary'];
			$url     = '/stellarwp/disconnect';
		}

		return sprintf(
			'<a href="%s" class="%s">%s</a>',
			$url,
			implode( ' ', $classes ),
			$message
		);
	}

}
