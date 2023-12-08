<?php declare( strict_types=1 );

namespace StellarWP\Uplink;

use StellarWP\ContainerContract\ContainerInterface;
use StellarWP\Uplink\Admin\License_Field;
use StellarWP\Uplink\API\V3\Auth\Contracts\Auth_Url;
use StellarWP\Uplink\API\V3\Auth\Token_Authorizer;
use StellarWP\Uplink\API\Validation_Response;
use StellarWP\Uplink\Auth\Admin\Disconnect_Controller;
use StellarWP\Uplink\Auth\Auth_Url_Builder;
use StellarWP\Uplink\Auth\Authorizer;
use StellarWP\Uplink\Auth\License\License_Manager;
use StellarWP\Uplink\Auth\Token\Token_Factory;
use StellarWP\Uplink\Components\Admin\Authorize_Button_Controller;
use StellarWP\Uplink\Resources\Collection;
use StellarWP\Uplink\Resources\Plugin;
use StellarWP\Uplink\Resources\Resource;
use StellarWP\Uplink\Resources\Service;
use StellarWP\Uplink\Site\Data;
use Throwable;

/**
 * Get the uplink container.
 *
 * @throws \RuntimeException
 *
 * @return ContainerInterface
 */
function get_container(): ContainerInterface {
	return Config::get_container();
}

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
		get_container()->get( Authorize_Button_Controller::class )
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
 * Get the stored authorization token, automatically detects multisite.
 *
 * @param  string  $slug  The plugin/service slug to use to determine if we use network/single site token storage.
 *
 * @throws \RuntimeException
 *
 * @return string|null
 */
function get_authorization_token( string $slug ): ?string {
	$resource = get_resource( $slug );

	if ( ! $resource ) {
		return null;
	}

	return get_container()->get( Token_Factory::class )->make( $resource )->get();
}

/**
 * Manually check if a license is authorized.
 *
 * @param  string  $license  The license key.
 * @param  string  $token  The stored token.
 * @param  string  $domain  The user's license domain.
 *
 * @return bool
 */
function is_authorized( string $license, string $token, string $domain ): bool {
	try {
		return get_container()
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
 * Manually check if a license is authorized by fetching required
 * data automatically.
 *
 * @param  string  $slug  The plugin/service slug.
 *
 * @return bool
 */
function is_authorized_by_resource( string $slug ): bool {
	try {
		$license = get_license_key( $slug );
		$token   = get_authorization_token( $slug );
		$domain  = get_license_domain();

		if ( ! $license || ! $token || ! $domain ) {
			return false;
		}

		return is_authorized( $license, $token, $domain );
	} catch ( Throwable $e ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( "An Authorization error occurred: {$e->getMessage()} {$e->getFile()}:{$e->getLine()} {$e->getTraceAsString()}" );
		}

		return false;
	}
}

/**
 * If the current user is allowed to perform token authorization.
 *
 * Without being filtered, this just runs a is_super_admin() check.
 *
 * @throws \RuntimeException
 *
 * @return bool
 */
function is_user_authorized(): bool {
	return get_container()->get( Authorizer::class )->can_auth();
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
		return get_container()->get( Auth_Url_Builder::class )
		                              ->build( $slug, $domain );
	} catch ( Throwable $e ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( "Error building auth URL: {$e->getMessage()} {$e->getFile()}:{$e->getLine()} {$e->getTraceAsString()}" );
		}

		return '';
	}
}

/**
 * Get a resource (plugin/service) from the collection.
 *
 * @param  string  $slug  The resource slug to find.
 *
 * @throws \RuntimeException
 *
 * @return Resource|Plugin|Service|null
 */
function get_resource( string $slug ) {
	return get_container()->get( Collection::class )->offsetGet( $slug );
}

/**
 * Compares the Uplink configuration to the current site this function is called on,
 * e.g. a sub-site to determine if the product supports multisite licenses.
 *
 * Not to be confused with Config::allows_network_licenses().
 *
 * @param  string|Resource|Plugin|Service  $slug_or_resource The product/service slug or a Resource object.
 *
 * @throws \RuntimeException
 *
 * @return bool
 */
function allows_multisite_license( $slug_or_resource ): bool {
	$resource = $slug_or_resource instanceof Resource ? $slug_or_resource : get_resource( $slug_or_resource );

	if ( ! $resource ) {
		return false;
	}

	return get_container()->get( License_Manager::class )->allows_multisite_license( $resource );
}

/**
 * A multisite aware license validation check.
 *
 * @param  string  $slug The plugin/service slug to validate against.
 * @param  string  $license An optional license key, otherwise we'll fetch it automatically.
 *
 * @throws \RuntimeException
 *
 * @return Validation_Response|null
 */
function validate_license( string $slug, string $license = '' ): ?Validation_Response {
	$resource = get_resource( $slug );

	if ( ! $resource ) {
		return null;
	}

	$license = $license ?: get_license_key( $slug );
	$network = allows_multisite_license( $resource );

	return $resource->validate_license( $license, $network );
}

/**
 * A multisite license aware way to get a resource's license key automatically
 * from the network or local site level.
 *
 * @param  string  $slug  The plugin/service slug.
 *
 * @throws \RuntimeException
 *
 * @return string
 */
function get_license_key( string $slug ): string {
	$resource = get_resource( $slug );

	if ( ! $resource ) {
		return '';
	}

	$network = allows_multisite_license( $resource );

	return $resource->get_license_key( $network ? 'network' : 'local' );
}

/**
 * A multisite license aware way to set a resource's license key automatically
 *  from the network or local site level.
 *
 * @param  string  $slug The plugin/service slug.
 * @param  string  $license The license key to store.
 *
 * @throws \RuntimeException
 *
 * @return bool
 */
function set_license_key( string $slug, string $license ): bool {
	$resource = get_resource( $slug );

	if ( ! $resource ) {
		return false;
	}

	$network = allows_multisite_license( $resource );

	return $resource->set_license_key( $license, $network ? 'network' : 'local' );
}

/**
 * Get the current site's license domain without any hash suffix.
 *
 * @throws \RuntimeException
 *
 * @return string
 */
function get_original_domain(): string {
	return get_container()->get( Data::class )->get_domain( true );
}

/**
 * Get the current site's license domain, multisite friendly.
 *
 * @throws \RuntimeException
 * @return string
 */
function get_license_domain(): string {
	return get_container()->get( Data::class )->get_domain();
}

/**
 * Get the disconnect token URL.
 *
 * @param  string  $slug The plugin/service slug.
 *
 * @throws \RuntimeException
 *
 * @return string
 */
function get_disconnect_url( string $slug ): string {
	return get_container()->get( Disconnect_Controller::class )->get_url( $slug );
}

/**
 * Get the License Field Object to render license key fields.
 *
 * @throws \RuntimeException
 *
 * @return License_Field
 */
function get_license_field(): License_Field {
	return get_container()->get( License_Field::class );
}

/**
 * Retrieve an Origin's auth url, if it exists.
 *
 * @param  string  $slug The product/service slug.
 *
 * @throws \RuntimeException
 *
 * @return string
 */
function get_auth_url( string $slug ): string {
	return get_container()->get( Auth_Url::class )->get( $slug );
}
