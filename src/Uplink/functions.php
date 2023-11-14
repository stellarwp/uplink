<?php declare( strict_types=1 );

namespace StellarWP\Uplink;

use StellarWP\Uplink\API\V3\Auth\Token_Authorizer;
use StellarWP\Uplink\Auth\Token\Contracts\Token_Manager;
use StellarWP\Uplink\Components\Admin\Authorize_Button_Controller;
use Throwable;

/**
 * Displays the token authorization button, which allows admins to
 * authorize their product through your origin server and clear the
 * token locally by disconnecting.
 *
 * @param string $slug The Product slug to render the button for.
 * @param string $domain An optional domain associated with a license key to pass along.
 */
function render_authorize_button( string $slug, string $domain = '' ): void {
	try {
		Config::get_container()->get( Authorize_Button_Controller::class )
			->render( [
				'slug'   => $slug,
				'domain' => $domain,
			] );
	} catch ( Throwable $e ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( "Unable to render authorize button: {$e->getMessage()} {$e->getFile()}:{$e->getLine()} {$e->getTraceAsString()}" );
		}
	}
}

/**
 * Get the stored authorization token.
 *
 * @return string|null
 */
function get_authorization_token(): ?string {
	try {
		return Config::get_container()->get( Token_Manager::class )->get();
	} catch ( Throwable $e ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( "Error occurred when fetching token: {$e->getMessage()} {$e->getFile()}:{$e->getLine()} {$e->getTraceAsString()}" );
		}

		return null;
	}
}

/**
 * Manually check if a license is authorized.
 *
 * @param  string  $license  The license key.
 * @param  string  $token  The stored token.
 * @param  string  $domain  The user's domain.
 *
 * @return bool
 */
function is_authorized( string $license, string $token, string $domain ): bool {
	try {
		return Config::get_container()
		             ->get( Token_Authorizer::class )
		             ->is_authorized( $license, $token, $domain );
	} catch ( Throwable $e ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( "An Authorization error occurred: {$e->getMessage()} {$e->getFile()}:{$e->getLine()} {$e->getTraceAsString()}" );
		}

		return false;
	}
}
