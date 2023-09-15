<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Auth;

final class Nonce {

	public const NONCE_ACTION = 'uplink_webhook_nonce';

	public function create(): string {
		return wp_create_nonce( self::NONCE_ACTION );
	}

	public function create_url( string $url ): string {
		return wp_nonce_url( $url, self::NONCE_ACTION, '_uplink_nonce' );
	}

}
