<?php declare(strict_types=1);

namespace StellarWP\Uplink\Admin;

use StellarWP\Uplink\API;
use StellarWP\Uplink\Config;
use StellarWP\Uplink\Resources\Collection;
use StellarWP\Uplink\Site\Data;

class Actions {

	const QUERY_VAR = 'stellarwp_action';

	/**
	 * @return void
	 * @action init
	 */
	public function register_route() {
		add_rewrite_endpoint( 'stellarwp', EP_ROOT, self::QUERY_VAR );
	}

	/**
	 * @param \WP $wp
	 *
	 * @return void
	 * @action parse_request
	 */
	public function handle_auth_request( $wp ) {
		if ( empty( $wp->query_vars[ self::QUERY_VAR ] ) ) {
			return;
		}

		$args = explode( '/', $wp->query_vars[ self::QUERY_VAR ] );

		if ( $args['disconnect'] ) {
			$this->handle_disconnect();
		}

		$this->handle_connect( $args );
	}

	/**
	 * Remove auth token§
	 */
	public function handle_disconnect() {
		$license    = $this->get_license_object();

		delete_option( sprintf( 'stellarwp_origin_%s_auth_token', $license->origin->slug ?? '' ) );

		wp_safe_redirect( wp_get_referer() );
		exit();
	}

	public function handle_connect( $args ) {
		if ( empty( $args['token'] ) ) {
			$url = $this->get_origin_url();
			if ( empty( $url ) ) {
				return;
			}

			$query_params = [
				'callback_uri' => sprintf( '%s/stellarwp/connect', get_site_url() ),
				'refer'		   => wp_get_referer(),
			];
			$url = sprintf( '%s?%s', $url, http_build_query( $query_params ) );

			wp_safe_redirect( $url );
			exit();
		}

		Config::get_container()->get( Data::class )->save_auth_token( $args['token'] );

		wp_safe_redirect( $args['refer'] );
		exit();
	}

	protected function get_origin_url() {
		$license = $this->get_license_object();
		$api     = Config::get_container()->get( API\Client::class );
		$origin  = $api->post('/origin', [ 'slug' => $license->get_slug() ] );

		if ( ! empty( $origin ) ) {
			return $origin->url . '/stellarwp_connect';
		}

		return '';
	}

	protected function get_license_object() {
		$collection = Config::get_container()->get( Collection::class );
		$plugin     = $collection->current();

		return $plugin->get_license_object();
	}
}
