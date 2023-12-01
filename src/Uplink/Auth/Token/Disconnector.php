<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Auth\Token;

use StellarWP\Uplink\Auth\Authorizer;
use StellarWP\Uplink\Auth\Token\Contracts\Token_Manager;
use StellarWP\Uplink\Resources\Collection;

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
	 * @var Collection
	 */
	private $resources;

	/**
	 * @param  Authorizer  $authorizer  Determines if the current user can perform actions.
	 * @param  Token_Manager  $token_manager The Token Manager.
	 * @param  Collection  $resources The resources collection.
	 */
	public function __construct(
		Authorizer $authorizer,
		Token_Manager $token_manager,
		Collection $resources
	) {
		$this->authorizer    = $authorizer;
		$this->token_manager = $token_manager;
		$this->resources     = $resources;
	}

	/**
	 * Delete a token if the current user is allowed to.
	 *
	 * @param string $slug The plugin or service slug.
	 */
	public function disconnect( string $slug ): bool {
		$plugin = $this->resources->offsetGet( $slug );

		if ( ! $plugin ) {
			return false;
		}

		if ( ! $this->authorizer->can_auth( $plugin ) ) {
			return false;
		}

		return $this->token_manager->delete();
	}

}
