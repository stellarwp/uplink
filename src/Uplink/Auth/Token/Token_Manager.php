<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Auth\Token;

use InvalidArgumentException;

/**
 * Manages storing authorization tokens in a network.
 *
 * @note All *_network_option() functions will fall back to
 * single site functions if multisite is not enabled.
 */
final class Token_Manager implements Contracts\Token_Manager {

	/**
	 * The option name to store the token in wp_options table.
	 *
	 * @see Config::set_token_auth_prefix()
	 *
	 * @var string
	 */
	protected $option_name;

	public function __construct( string $option_name ) {
		if ( ! $option_name ) {
			throw new InvalidArgumentException( 'You must set a token prefix with StellarWP\Uplink\Config::set_token_auth_prefix() before using the token manager.' );
		}

		$this->option_name = $option_name;
	}

	/**
	 * Returns the option_name that is used to store tokens.
	 *
	 * @return string
	 */
	public function option_name(): string {
		return $this->option_name;
	}

	/**
	 * Validates a token is in the accepted UUIDv4 format.
	 *
	 * @param  string  $token
	 *
	 * @return bool
	 */
	public function validate( string $token ): bool {
		$pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';

		return preg_match( $pattern, $token ) === 1;
	}

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
	 * @return bool
	 */
	public function delete(): bool {
		// Already doesn't exist, WordPress would normally return false.
		if ( $this->get() === null ) {
			return true;
		}

		return delete_network_option( get_current_network_id(), $this->option_name );
	}

}
