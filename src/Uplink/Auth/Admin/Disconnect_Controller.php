<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Auth\Admin;

use StellarWP\Uplink\Auth\Token\Disconnector;

final class Disconnect_Controller {

	public const ARG = 'uplink_disconnect';

	/**
	 * @var Disconnector
	 */
	private $disconnect;

	public function __construct( Disconnector $disconnect ) {
		$this->disconnect = $disconnect;
	}

	/**
	 * Disconnect (delete) a token if the user is allowed to.
	 *
	 * @action admin_init
	 *
	 * @return void
	 */
	public function maybe_disconnect(): void {
		if ( empty( $_GET[ self::ARG ] ) || empty( $_GET[ '_wpnonce'] ) ) {
			return;
		}

		if ( ! is_admin() || wp_doing_ajax() ) {
			return;
		}

		if ( ! wp_verify_nonce( $_GET[ '_wpnonce' ], self::ARG ) ) {
			return;
		}

		if ( ! $this->disconnect->disconnect() ) {
			// TODO: should add a notice and/or logging that this failed.
		}

		$referrer = wp_get_referer();

		if ( $referrer ) {
			wp_safe_redirect( $referrer );
			exit;
		}
	}

}
