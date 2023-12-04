<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Auth\Token;

use StellarWP\Uplink\Auth\Authorizer;
use StellarWP\Uplink\Auth\Token\Exceptions\InvalidTokenException;
use StellarWP\Uplink\Resources\Resource;

final class Connector {

	/**
	 * @var Authorizer
	 */
	private $authorizer;

	/**
	 * @var Token_Manager_Factory
	 */
	private $token_manager_factory;

	/**
	 * @param  Authorizer  $authorizer  Determines if the current user can perform actions.
	 * @param  Token_Manager_Factory  $token_manager_factory  The Token Manager Factory.
	 */
	public function __construct(
		Authorizer $authorizer,
		Token_Manager_Factory $token_manager_factory
	) {
		$this->authorizer            = $authorizer;
		$this->token_manager_factory = $token_manager_factory;
	}

	/**
	 * Store a token if the user is allowed to.
	 *
	 * @throws InvalidTokenException
	 */
	public function connect( string $token, Resource $resource ): bool {
		if ( ! $this->authorizer->can_auth( $resource ) ) {
			return false;
		}

		$token_manager = $this->token_manager_factory->make( $resource->is_network_activated() );

		if ( ! $token_manager->validate( $token ) ) {
			throw new InvalidTokenException( 'Invalid token format' );
		}

		return $token_manager->store( $token );
	}

}
