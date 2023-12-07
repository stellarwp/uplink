<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Auth\Token\Managers;

use RuntimeException;

/**
 * Manages storing authorization tokens in a network, used when a
 * plugin is network activated.
 *
 * @note All *_network_option() functions will fall back to
 * single site functions if multisite is not enabled.
 */
final class Network_Token_Manager extends Token_Manager {

	/**
	 * Store the token.
	 *
	 * @param  string  $token
	 *
	 * @throws RuntimeException
	 *
	 * @return bool
	 */
	public function store( string $token ): bool {
		$this->multisite_check();

		if ( ! $token ) {
			return false;
		}

		// WordPress would otherwise return false if the items match.
		if ( $token === $this->get() ) {
			return true;
		}

		return update_network_option( get_current_network_id(), $this->option_name, $token );
	}

	/**
	 * Get the token.
	 *
	 * @throws RuntimeException
	 *
	 * @return string|null
	 */
	public function get(): ?string {
		$this->multisite_check();

		return get_network_option( get_current_network_id(), $this->option_name, null );
	}

	/**
	 * Revoke the token.
	 *
	 * @throws RuntimeException
	 *
	 * @return bool
	 */
	public function delete(): bool {
		$this->multisite_check();

		// Already doesn't exist, WordPress would normally return false.
		if ( $this->get() === null ) {
			return true;
		}

		return delete_network_option( get_current_network_id(), $this->option_name );
	}

	/**
	 * Check if multisite is enabled.
	 *
	 * @throws RuntimeException
	 *
	 * @return void
	 */
	private function multisite_check(): void {
		if ( ! is_multisite() ) {
			throw new RuntimeException( 'Multisite is not enabled' );
		}
	}

}
