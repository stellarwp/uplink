<?php declare( strict_types=1 );

namespace StellarWP\Uplink\API\V3;

use StellarWP\Uplink\API\V3\Auth\Auth_Url_Cache_Decorator;
use StellarWP\Uplink\API\V3\Auth\Contracts\Auth_Url;
use StellarWP\Uplink\API\V3\Contracts\Client_V3;
use StellarWP\Uplink\Config;
use StellarWP\Uplink\Contracts\Abstract_Provider;
use WP_Http;

final class Provider extends Abstract_Provider {

	/**
	 * @inheritDoc
	 */
	public function register() {
		$this->container->bind( Auth_Url::class, Auth_Url_Cache_Decorator::class );

		$this->container->singleton( Client_V3::class, static function (): Client {
			$prefix = 'stellarwp/uplink/' . Config::get_hook_prefix();

			$api_root = '/api/stellarwp/v3/';

			if ( defined( 'STELLARWP_UPLINK_V3_API_ROOT' ) && STELLARWP_UPLINK_V3_API_ROOT ) {
				$api_root = STELLARWP_UPLINK_V3_API_ROOT;
			}

			$base_url = 'https://pue.theeventscalendar.com';

			if ( defined( 'STELLARWP_UPLINK_API_BASE_URL' ) && STELLARWP_UPLINK_API_BASE_URL ) {
				$base_url = preg_replace( '!/$!', '', STELLARWP_UPLINK_API_BASE_URL );
			}

			/**
			 * Filter the V3 api root.
			 *
			 * @param  string  $api_root  The base endpoint for the v3 API.
			 */
			$api_root = apply_filters( $prefix . '/v3/client/api_root', $api_root );

			/**
			 * Filter the V3 api base URL.
			 *
			 * @param  string  $base_url  The base URL for the v3 API.
			 */
			$base_url = apply_filters( $prefix . '/v3/client/base_url', $base_url );

			$request_args = apply_filters( $prefix . '/v3/client/request_args', [
				'headers' => [
					'Content-Type' => 'application/json',
				],
				'timeout' => 15, // Seconds.
			] );

			return new Client( $api_root, $base_url, $request_args, new WP_Http() );
		} );
	}

}
