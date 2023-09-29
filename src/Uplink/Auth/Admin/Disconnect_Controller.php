<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Auth\Admin;

use StellarWP\Uplink\Auth\Token\Disconnector;
use StellarWP\Uplink\Notice\Notice_Handler;
use StellarWP\Uplink\Notice\Notice;

final class Disconnect_Controller {

	public const ARG = 'uplink_disconnect';

	/**
	 * @var Disconnector
	 */
	private $disconnect;

	/**
	 * @var Notice_Handler
	 */
	private $notice;

	public function __construct( Disconnector $disconnect, Notice_Handler $notice ) {
		$this->disconnect = $disconnect;
		$this->notice     = $notice;
	}

	/**
	 * Disconnect (delete) a token if the user is allowed to.
	 *
	 * @action admin_init
	 *
	 * @return void
	 */
	public function maybe_disconnect(): void {
		if ( empty( $_GET[ self::ARG ] ) || empty( $_GET['_wpnonce'] ) ) {
			return;
		}

		if ( ! is_admin() || wp_doing_ajax() ) {
			return;
		}

		if ( wp_verify_nonce( $_GET['_wpnonce'], self::ARG ) ) {
			if ( $this->disconnect->disconnect() ) {
				$this->notice->add(
					new Notice( Notice::SUCCESS,
						__( 'Token disconnected.', '%TEXTDOMAIN%' ),
						true
					)
				);
			} else {
				$this->notice->add(
					new Notice( Notice::ERROR,
						__( 'Unable to disconnect token, ensure you have admin permissions.', '%TEXTDOMAIN%' ),
						true
					)
				);
			}
		} else {
			$this->notice->add(
				new Notice( Notice::ERROR,
					__( 'Unable to disconnect token: nonce verification failed.', '%TEXTDOMAIN%' ),
					true
				)
			);
		}

		$referrer = wp_get_referer();

		if ( $referrer ) {
			wp_safe_redirect( $referrer );
			exit;
		}
	}

}
