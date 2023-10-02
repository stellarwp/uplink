<?php declare( strict_types=1 );

namespace StellarWP\Uplink;

use StellarWP\Uplink\Components\Admin\Authorize_Button_Controller;
use Throwable;

/**
 * Displays the token authorization button, which allows admins to
 * authorize their product through your origin server and clear the
 * token locally by disconnecting.
 *
 * @param string $slug The Product slug to render the button for.
 */
function render_authorize_button( string $slug ): void {
	try {
		Config::get_container()->get( Authorize_Button_Controller::class )
		                       ->render( [ 'slug' => $slug ] );
	} catch ( Throwable $e ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( "Unable to render authorize button: {$e->getMessage()} {$e->getFile()}:{$e->getLine()} {$e->getTraceAsString()}" );
		}
	}
}
