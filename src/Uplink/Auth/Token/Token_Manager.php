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
	 * Resources collection
	 *
	 * @since TBD
	 *
	 * @var Collection
	 */
	protected $collection;

	/**
	 * @param  string  $option_name  The option name as set via Config::set_token_auth_prefix().
	 */
	public function __construct( string $option_name, Collection $collection ) {
		if ( ! $option_name ) {
			throw new InvalidArgumentException(
				__( 'You must set a token prefix with StellarWP\Uplink\Config::set_token_auth_prefix() before using the token manager.', '%TEXTDOMAIN%' )
			);
		}

		$this->option_name = $option_name;
		$this->collection  = $collection;
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

		$validated_slug = $this->validate_slug( $slug );

		if ( $slug && ! $validated_slug ) {
			return false;
		}

		$current_value = $this->get( is_string( $validated_slug ) ? $validated_slug : '' );

		// WordPress would otherwise return false if the items match.
		if ( $token === $current_value ) {
			return true;
		}

		$values = $this->get_all();

		$values[ $validated_slug ] = $token;

		return update_network_option( get_current_network_id(), $this->option_name, $values );
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
		$validated_slug = $this->validate_slug( $slug );

		if ( $slug && ! $validated_slug ) {
			return null;
		}

		$values = $this->get_all();

		if ( ! $values ) {
			return null;
		}

		if ( ! $validated_slug ) {
			return array_values( $values )[0];
		}

		return $values[ $validated_slug ] ?? null;
	}

	/**
	 * Get all the tokens.
	 *
	 * @since TBD
	 *
	 * @return array<string, string>|array{}
	 */
	public function get_all(): array {
		return (array) get_network_option( get_current_network_id(), $this->option_name, [] );
	}

	/**
	 * Revoke the token.
	 *
	 * @return bool
	 */
	public function delete( string $slug = '' ): bool {
		$current_value = $this->get_all();
		// Already doesn't exist, WordPress would normally return false.
		if ( ! $current_value ) {
			return true;
		}

		$validated_slug = $this->validate_slug( $slug );

		if ( $slug && ! $validated_slug ) {
			return false;
		}

		if ( ! $validated_slug ) {
			if ( ! isset( $current_value[ $validated_slug ] ) ) {
				return true;
			}

			unset( $current_value[ $validated_slug ] );
			return update_network_option( get_current_network_id(), $this->option_name, $current_value );
		}

		if ( empty( $current_value[ $validated_slug ] ) ) {
			return true;
		}

		unset( $current_value[ $validated_slug ] );

		return update_network_option( get_current_network_id(), $this->option_name, $current_value );
	}

	/**
	 * Validates the slug is a valid key in the collection.
	 *
	 * @since TBD
	 *
	 * @param  string  $slug
	 *
	 * @return string|int The slug if valid, 0 if not.
	 */
	protected function validate_slug( string $slug = '' ) {
		return $slug && $this->collection->offsetExists( $slug ) ? $slug : 0;
	}
}
