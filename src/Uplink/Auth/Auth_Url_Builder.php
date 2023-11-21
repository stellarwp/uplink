<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Auth;

use StellarWP\Uplink\API\V3\Auth\Contracts\Auth_Url;

final class Auth_Url_Builder {

	/**
	 * @var Nonce
	 */
	private $nonce;

	/**
	 * @var Auth_Url
	 */
	private $auth_url_manager;

	/**
	 * @param  Nonce  $nonce  The Nonce creator.
	 * @param  Auth_Url  $auth_url_manager  The auth URL manager.
	 */
	public function __construct(
		Nonce $nonce,
		Auth_Url $auth_url_manager
	) {
		$this->nonce            = $nonce;
		$this->auth_url_manager = $auth_url_manager;
	}

	/**
	 * Build a brand's authorization URL, with the uplink_callback base64 query variable.
	 *
	 * @param  string  $slug  The product/service slug.
	 * @param  string  $domain  An optional domain associated with a license key to pass along.
	 *
	 * @return string
	 */
	public function build( string $slug, string $domain = '' ): string {
		global $pagenow;

		if ( empty( $pagenow ) ) {
			return '';
		}

		$auth_url = $this->auth_url_manager->get( $slug );

		if ( ! $auth_url ) {
			return '';
		}

		// Query arguments to combine with $_GET and add to the authorization URL.
		$args = [
			'uplink_domain' => $domain,
			'uplink_slug'   => $slug,
		];

		$url = add_query_arg(
			array_filter( array_merge( $_GET, $args ) ),
			admin_url( $pagenow )
		);

		return sprintf( '%s?%s',
			$auth_url,
			http_build_query( [
				'uplink_callback' => base64_encode( $this->nonce->create_url( $url ) ),
			] )
		);
	}

}
