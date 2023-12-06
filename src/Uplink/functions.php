<?php declare( strict_types=1 );

namespace StellarWP\Uplink;

use StellarWP\Uplink\API\V3\Auth\Token_Authorizer;
use StellarWP\Uplink\Auth\Auth_Url_Builder;
use StellarWP\Uplink\Auth\License\License_Manager;
use StellarWP\Uplink\Auth\Token\Token_Factory;
use StellarWP\Uplink\Components\Admin\Authorize_Button_Controller;
use StellarWP\Uplink\Resources\Collection;
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
 * @param  string  $slug  The plugin/service slug to use to determine if we use network/single site token storage.
 *
 * @throws \RuntimeException
 *
 * @return string|null
 */
function get_authorization_token( string $slug ): ?string {
	$container = Config::get_container();

	try {
		$plugin = $container->get( Collection::class )->offsetGet( $slug );

		if ( ! $plugin ) {
			return null;
		}

		return $container->get( Token_Factory::class )
		                 ->make( $plugin )
		                 ->get();
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

/**
 * Build a brand's authorization URL, with the uplink_callback base64 query variable.
 *
 * @param  string  $slug  The Product slug to render the button for.
 * @param  string  $domain  An optional domain associated with a license key to pass along.
 *
 * @return string
 */
function build_auth_url( string $slug, string $domain = '' ): string {
	try {
		return Config::get_container()->get( Auth_Url_Builder::class )
		                              ->build( $slug, $domain );
	} catch ( Throwable $e ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( "Error building auth URL: {$e->getMessage()} {$e->getFile()}:{$e->getLine()} {$e->getTraceAsString()}" );
		}

		return '';
	}
}

/**
 * A multisite license aware way to get a resource's license key either
 * from the network or local site level.
 *
 * @param  string  $slug  The plugin/service slug.
 *
 * @throws \RuntimeException
 *
 * @return string
 */
function get_license_key( string $slug ): string {
	$container = Config::get_container();
	$resource  = $container->get( Collection::class )->offsetGet( $slug );

	if ( ! $resource ) {
		return '';
	}

	$network = $container->get( License_Manager::class )->allows_multisite_license( $resource );

	return $resource->get_license_key( $network ? 'network' : 'local' );
}
