<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Auth;

use StellarWP\Uplink\Config;

final class Nonce {

	/**
	 * The suffix for the transient name to store the nonce.
	 */
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
	public function __construct( int $expiration = 2100 ) {
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

	/**
	 * Attach a nonce to a URL.
	 *
	 * @note Unlike WordPress' function, you should escape this manually.
	 *
	 * @param  string  $url The existing URL to attach the nonce to.
	 *
	 * @return string
	 */
	public function create_url( string $url ): string {
		return esc_url_raw( add_query_arg( '_uplink_nonce', $this->create(), $url ) );
	}

	/**
	 * Get the transient key, combining the configured hook prefix with our suffix.
	 *
	 * @return string
	 */
	private function key(): string {
		return Config::get_hook_prefix_underscored() . self::NONCE_SUFFIX;
	}

}
