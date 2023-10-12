<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Auth;

use StellarWP\Uplink\Config;

final class Nonce {

	public const NONCE_SUFFIX = '_uplink_nonce';

	/**
	 * How long a nonce is valid for in seconds.
	 *
	 * @var int
	 */
	private $expiration;

	/**
	 * @param  int  $expiration  How long the nonce is valid for in seconds.
	 */
	public function __construct( int $expiration = 900 ) {
		$this->expiration = $expiration;
	}

	/**
	 * Verify a nonce.
	 *
	 * @param  string  $nonce The nonce token.
	 *
	 * @return bool
	 */
	public static function verify( string $nonce ): bool {
		if ( ! $nonce ) {
			return false;
		}

		return $nonce === get_transient( Config::get_hook_prefix_underscored() . self::NONCE_SUFFIX );
	}

	/**
	 * Create or reuse a non-expired nonce.
	 *
	 * @return string
	 */
	public function create(): string {
		$existing = get_transient( $this->key() );

		if ( $existing ) {
			return $existing;
		}

		$nonce = wp_generate_password( 16, false );

		set_transient( $this->key(), $nonce, $this->expiration );

		return $nonce;
	}

	public function create_url( string $url ): string {
		$url = str_replace( '&amp;', '&', $url );

		return esc_html( add_query_arg( '_uplink_nonce', $this->create(), $url ) );
	}

	private function key(): string {
		return Config::get_hook_prefix_underscored() . self::NONCE_SUFFIX;
	}

}
