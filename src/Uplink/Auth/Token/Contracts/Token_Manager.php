<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Auth\Token\Contracts;

/**
 * @internal
 */
interface Token_Manager {

	/**
	 * This makes up the suffix of the option name when combined
	 * with the custom token prefix.
	 *
	 * @see Config::set_token_auth_prefix()
	 */
	public const TOKEN_SUFFIX = 'uplink_auth_token';

	/**
	 * Returns the option_name/network_option_name that is used to store tokens.
	 *
	 * @return string
	 */
	public function option_name(): string;


	/**
	 * Validates a token is in the accepted UUIDv4 format.
	 *
	 * @param  string  $token
	 *
	 * @return bool
	 */
	public function validate( string $token ): bool;

	/**
	 * Stores the token in the database.
	 *
	 * @param  string  $token
	 *
	 * @return bool
	 */
	public function store( string $token ): bool;

	/**
	 * Retrieves the stored token.
	 *
	 * @return string|null
	 */
	public function get(): ?string;

	/**
	 * Deletes the token from the database.
	 *
	 * @return bool
	 */
	public function delete(): bool;

}
