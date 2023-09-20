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

	public function __construct(
		Authorizer $authorizer,
		Token_Manager $token_manager
	) {
		$this->authorizer    = $authorizer;
		$this->token_manager = $token_manager;
	}

	/**
	 * Delete a token if the current user is allowed to.
	 */
	public function disconnect(): bool {
		if ( ! $this->authorizer->can_auth() ) {
			return false;
		}

		return $this->token_manager->delete();
	}

}
