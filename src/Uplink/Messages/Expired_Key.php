<?php

namespace StellarWP\Uplink\Messages;

class Expired_Key extends Message_Abstract {
	/**
	 * @inheritDoc
	 */
	public function get(): string {
        $message  = '<div class="notice notice-warning"><p>';
        $message  .= __( 'Your license is expired', 'stellar-uplink-client' );
		$message .= '<a href="https://evnt.is/195y" target="_blank" class="button button-primary">' .
			__( 'Renew Your License Now', 'stellar-uplink-client' ) .
			'<span class="screen-reader-text">' .
			__( ' (opens in a new window)', 'stellar-uplink-client' ) .
			'</span></a>';
        $message .= '</p>    </div>';

		return $message;
	}
}
