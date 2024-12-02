<?php

namespace StellarWP\Uplink\Messages;
use StellarWP\Uplink\Config;

class Expired_Key extends Message_Abstract {
	/**
	 * @inheritDoc
	 */
	public function get(): string {
		$message_link = apply_filters( 'stellarwp/uplink/' . Config::get_hook_prefix() . '/messages/expired_key_link', 'https://evnt.is/195y' );

		$message_content = __( 'Your license is expired', '%TEXTDOMAIN%' );
		$message_content .= '<a href="' . esc_url( $message_link ) . '" target="_blank" class="button button-primary">' .
			__( 'Renew Your License Now', '%TEXTDOMAIN%' ) .
			'<span class="screen-reader-text">' .
			__( ' (opens in a new window)', '%TEXTDOMAIN%' ) .
			'</span></a>';
		$message_content = apply_filters( 'stellarwp/uplink/' . Config::get_hook_prefix() . '/messages/expired_key', $message_content );
		
		$message = '<div class="notice notice-warning"><p>';
		$message .= $message_content;
        $message .= '</p></div>';

		return $message;
	}
}
