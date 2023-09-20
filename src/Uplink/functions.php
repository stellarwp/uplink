<?php declare( strict_types=1 );

namespace StellarWP\Uplink;

use StellarWP\Uplink\Components\Authorize_Button_Controller;
use Throwable;

/**
 * Displays the token authorization button, which allows admins to
 * authorize their product through your origin server and clear the
 * token locally by disconnecting.
 */
function render_authorize_button(): void {
	try {
		Config::get_container()->get( Authorize_Button_Controller::class )->render();
	} catch ( Throwable $e ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( "Unable to render authorize button: {$e->getMessage()} {$e->getFile()}:{$e->getLine()}" );
		}
	}
}
