<?php

namespace StellarWP\Uplink\Messages;

class Network_Unlicensed extends Message_Abstract {
	/**
	 * @inheritDoc
	 */
	public function get(): string {
		return esc_html__( 'No license entered. Consult your network administrator.', '%stellar-uplink-domain%' );
	}
}
