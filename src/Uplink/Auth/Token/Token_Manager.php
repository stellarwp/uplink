<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Auth\Token;

use StellarWP\Uplink\Resources\Collection;
use InvalidArgumentException;
use StellarWP\Uplink\Config;

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

	/**
	 * @param  string  $option_name  The option name as set via Config::set_token_auth_prefix().
	 */
	public function __construct( string $option_name ) {
		if ( ! $option_name ) {
			throw new InvalidArgumentException(
				__( 'You must set a token prefix with StellarWP\Uplink\Config::set_token_auth_prefix() before using the token manager.', '%TEXTDOMAIN%' )
			);
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
	 * @since TBD Added $slug param.
	 *
	 * @param  string  $token
	 * @param  string  $slug The Product slug to store the token for.
	 *
	 * @return bool
	 */
	public function store( string $token, string $slug = '' ): bool {
		if ( ! $token ) {
			return false;
		}

		$slug = $this->validate_slug( $slug );

		$current_value = $this->get( $slug );

		// WordPress would otherwise return false if the items match.
		if ( $token === $current_value ) {
			return true;
		}

		$data = $this->get_all();

		if ( ! is_array( $data ) ) {
			$data = [];
		}

		$data[ $slug ] = $token;

		return update_network_option( get_current_network_id(), $this->option_name, $data );
	}

	/**
	 * Get the token.
	 *
	 * @since TBD Added $slug param. Changed return type to mixed.
	 *
	 * @param  string  $slug The Product slug to retrieve the token for.
	 *
	 * @return string|null
	 */
	public function get( string $slug = '' ): ?string {
		$slug = $this->validate_slug( $slug );

		$value = get_network_option( get_current_network_id(), $this->option_name, null );

		if ( ! $value ) {
			return null;
		}

		if ( ! $slug ) {
			return is_string( $value ) ? $value : ( array_values( $value )[0] ?? null );
		}

		if ( is_string( $value ) ) {
			// Still using old structure, lets return whatever we found.
			return $value;
		}

		return $value[ $slug ] ?? null;
	}

	/**
	 * Get all the tokens.
	 *
	 * @since TBD
	 *
	 * @return null|string|array
	 */
	public function get_all() {
		return get_network_option( get_current_network_id(), $this->option_name, [] );
	}

	/**
	 * Revoke the token.
	 *
	 * @return bool
	 */
	public function delete( string $slug = '' ): bool {
		$current_value = $this->get_all();
		// Already doesn't exist, WordPress would normally return false.
		if ( $current_value === null ) {
			return true;
		}

		$slug = $this->validate_slug( $slug );

		if ( ! $slug ) {
			return delete_network_option( get_current_network_id(), $this->option_name );
		}

		if ( empty( $current_value[ $slug ] ) ) {
			return true;
		}

		unset( $current_value[ $slug ] );

		return update_network_option( get_current_network_id(), $this->option_name, $current_value );
	}

	/**
	 * Validates the slug is a valid key in the collection.
	 *
	 * @since TBD
	 *
	 * @param  string  $slug
	 *
	 * @return string
	 */
	protected function validate_slug( string $slug = '' ): string {
		return $slug && Config::get_container()->get( Collection::class )->offsetExists( $slug ) ? $slug : '';
	}
}
