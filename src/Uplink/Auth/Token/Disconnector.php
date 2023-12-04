<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Auth\Token;

use StellarWP\Uplink\Auth\Authorizer;
use StellarWP\Uplink\Resources\Collection;

final class Disconnector {

	/**
	 * @var Authorizer
	 */
	private $authorizer;

	/**
	 * @var Token_Manager_Factory
	 */
	private $token_manager_factory;

	/**
	 * @var Collection
	 */
	private $resources;

	/**
	 * @param  Authorizer  $authorizer  Determines if the current user can perform actions.
	 * @param  Token_Manager_Factory  $token_manager_factory  The Token Manager Factory.
	 * @param  Collection  $resources  The resources collection.
	 */
	public function __construct(
		Authorizer $authorizer,
		Token_Manager_Factory $token_manager_factory,
		Collection $resources
	) {
		$this->authorizer            = $authorizer;
		$this->token_manager_factory = $token_manager_factory;
		$this->resources             = $resources;
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

		return $this->token_manager_factory->make( $plugin->is_network_activated() )->delete();
	}

}
