<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Auth\Token;

use StellarWP\Uplink\Auth\Token\Contracts\Token_Manager;

/**
 * Manages storing authorization tokens in a network.
 *
 * @note You should always use the Factory to make this instance.
 *
 * @see Token_Manager_Factory::make()
 */
class Network_Token_Manager extends Option_Token_Manager implements Token_Manager {

	/**
	 * Store the token.
	 *
	 * @param  string  $token
	 *
	 * @return bool
	 */
	public function store( string $token ): bool {
		if ( ! $token ) {
			return false;
		}

		return update_network_option( get_current_network_id(), $this->option_name, $token );
	}

	/**
	 * Get the token.
	 *
	 * @return string|null
	 */
	public function get(): ?string {
		return get_network_option( get_current_network_id(), $this->option_name, null );
	}

	/**
	 * Revoke the token.
	 *
	 * @return void
	 */
	public function delete(): void {
		delete_network_option( get_current_network_id(), $this->option_name );
	}

}
