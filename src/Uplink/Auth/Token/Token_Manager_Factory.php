<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Auth\Token;

use InvalidArgumentException;
use StellarWP\Uplink\Auth\Token\Contracts\Token_Manager;

use function strlen;

final class Token_Manager_Factory {

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
	 * Creates a token manager instance based on if we should be storing tokens
	 * in the network options or not.
	 *
	 * @return Token_Manager
	 */
	public function make(): Token_Manager {
		return $this->should_use_network() ? new Network_Token_Manager( $this->option_name ) : new Option_Token_Manager( $this->option_name );
	}

	/**
	 * If the sub-site starts with the same URL as the main site URL, we must store our tokens in network options,
	 * otherwise, we'll store the tokens in the sub-site's options table.
	 *
	 * @return bool
	 */
	private function should_use_network(): bool {
		if ( ! is_multisite() ) {
			return false;
		}

		$id = get_main_site_id();

		if ( ! $id ) {
			return false;
		}

		$main_site_url    = get_site_url( $id );
		$current_site_url = get_site_url();

		return 0 === strncmp( $current_site_url, $main_site_url, strlen( $main_site_url ) );
	}

}
