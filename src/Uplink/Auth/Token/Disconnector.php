<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Auth\Token;

use StellarWP\Uplink\Auth\Authorizer;
use StellarWP\Uplink\Auth\Token\Contracts\Token_Manager;

final class Disconnector {

	/**
	 * @var Authorizer
	 */
	private $authorizer;

	/**
	 * @var Token_Manager
	 */
	private $token_manager;

	/**
	 * @param  Authorizer  $authorizer  Determines if the current user can perform actions.
	 * @param  Token_Manager  $token_manager The Token Manager.
	 */
	public function __construct(
		Authorizer $authorizer,
		Token_Manager $token_manager
	) {
		$this->authorizer    = $authorizer;
		$this->token_manager = $token_manager;
	}

	/**
	 * Delete a token if the current user is allowed to.
	 *
	 * @since TBD Added $slug param.
	 *
	 * @param  string  $slug  The Product slug to disconnect the token for.
	 *
	 * @return bool
	 */
	public function disconnect( string $slug = '' ): bool {
		if ( ! $this->authorizer->can_auth() ) {
			return false;
		}

		return $this->token_manager->delete( $slug );
	}

}
