<?php

namespace StellarWP\Uplink\Messages;

class Valid_Key_New extends Valid_Key {
	/**
	 * @inheritDoc
	 */
	public function get(): string {
		$message = sprintf(
			__( 'Thanks for setting up a valid key. It will expire on %s.', 'stellar-uplink' ),
			$this->expiration
		);

		return esc_html( $message );
	}
}
