<?php declare(strict_types=1);

namespace StellarWP\Uplink\Admin;

use StellarWP\Uplink\API;
use StellarWP\Uplink\Config;
use StellarWP\Uplink\Resources\Collection;
use StellarWP\Uplink\Site\Data;
use StellarWP\Uplink\Utils\Namespaces;

class Actions {

	const QUERY_VAR = 'stellarwp_action';

	/**
	 * Register handle route for connect/disconnect
	 *
	 * @since 1.0.1
	 *
	 * @return void
	 *
	 * @action init
	 */
	public function register_route() {
		add_rewrite_endpoint( 'stellarwp', EP_ROOT, self::QUERY_VAR );
	}

	/**
	 * Handle auth connect and disconnect request
	 *
	 * @since 1.0.1
	 *
	 * @param \WP $wp
	 *
	 * @return void
	 *
	 * @action parse_request
	 */
	public function handle_auth_request( $wp ) {
		if ( empty( $wp->query_vars[ self::QUERY_VAR ] ) ) {
			return;
		}

		$args = apply_filters( Namespaces::get_hook_name( 'auth/request_args', '%TEXTDOMAIN%' ) , explode( '/', $wp->query_vars[ self::QUERY_VAR ] ) );

		if ( ! empty( $args['disconnect'] ) ) { // @phpstan-ignore-line
			$this->handle_disconnect();
		}

		$this->handle_connect( $args );
	}

	/**
	 * Remove auth tokens and redirect back to settings page
	 *
	 * @since 1.0.1
	 */
	public function handle_disconnect() {
		$license = $this->get_license_object();

		do_action( Namespaces::get_hook_name( 'disconnect/before/redirect', '%TEXTDOMAIN%' ), $license );

		delete_option( sprintf( '%s%s_auth_token', Namespaces::get_option_name( 'origin', '%TEXTDOMAIN%' ), $license->origin->slug ?? '' ) );

		do_action( Namespaces::get_hook_name( 'disconnect/after/redirect', '%TEXTDOMAIN%' ), $license );

		wp_safe_redirect( wp_get_referer() );
		exit();
	}

	/**
	 * Save auth token and redirect back to referer URL
	 *
	 * @since 1.0.1
	 *
	 * @param array $args
	 */
	public function handle_connect( $args ) {
		if ( empty( $args['token'] ) ) {
			$url = $this->get_origin_url();
			if ( empty( $url ) ) {
				return;
			}

			$query_params = [
				'callback_uri' => urlencode( sprintf( '%s/%s', get_site_url(), Namespaces::get_hook_name( 'connect', '%TEXTDOMAIN%' ) ) ),
				'refer'		   => urlencode( wp_get_referer() ),
			];
			$url 		  = sprintf( '%s/%s?%s', $url, Namespaces::get_hook_name( 'oauth_connect/login' ), http_build_query( $query_params ) );

			wp_safe_redirect( $url );
			exit();
		}

		do_action( Namespaces::get_hook_name( 'disconnect/before/save_auth_token', '%TEXTDOMAIN%' ), $args );

		Config::get_container()->get( Data::class )->save_auth_token( $args['token'] );

		do_action( Namespaces::get_hook_name( 'disconnect/after/save_auth_token', '%TEXTDOMAIN%' ), $args );

		wp_safe_redirect( $args['refer'] );
		exit();
	}

	/**
	 * Retrieve origin URL from server
	 *
	 * @since 1.0.1
	 *
	 * @return string
	 */
	protected function get_origin_url() {
		$license = $this->get_license_object();
		$api     = Config::get_container()->get( API\Client::class );
		$origin  = $api->post('/origin', [ 'slug' => $license->get_slug() ] );

		if ( ! empty( $origin ) ) {
			return $origin->url . '/stellarwp_connect';
		}

		return '';
	}

	/**
	 * Retrieve License
	 *
	 * @since 1.0.1
	 *
	 * @return mixed
	 */
	protected function get_license_object() {
		$collection = Config::get_container()->get( Collection::class );
		$plugin     = $collection->current();

		return $plugin->get_license_object();
	}
}
