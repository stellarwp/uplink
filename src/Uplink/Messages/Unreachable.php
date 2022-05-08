<?php

namespace StellarWP\Uplink\Messages;

class Unreachable extends Message_Abstract {
	/**
	 * @inheritDoc
	 */
	public function get(): string {
		$message = esc_html__( 'Sorry, key validation server is not available.', 'stellar-uplink' );

		return $message;
	}
}
