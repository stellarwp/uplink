<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Auth\Token;

use StellarWP\Uplink\Resources\Collection;

final class Disconnector {

	/**
	 * @var Token_Factory
	 */
	private $token_manager_factory;

	/**
	 * @var Collection
	 */
	private $resources;

	/**
	 * @param  Token_Factory  $token_manager_factory  The Token Manager Factory.
	 * @param  Collection  $resources  The resources collection.
	 */
	public function __construct(
		Token_Factory $token_manager_factory,
		Collection $resources
	) {
		$this->token_manager_factory = $token_manager_factory;
		$this->resources             = $resources;
	}

	/**
	 * Delete a token if the current user is allowed to.
	 *
	 * @param  string  $slug       The plugin or service slug.
	 * @param  string  $cache_key  The token cache key.
	 *
	 * @return bool
	 */
	public function disconnect( string $slug, string $cache_key ): bool {
		$plugin = $this->resources->offsetGet( $slug );

		if ( ! $plugin ) {
			return false;
		}

		$result = $this->token_manager_factory->make( $plugin )->delete();

		if ( $result ) {
			// Delete the authorization cache.
			delete_transient( $cache_key );
		}

		return $result;
	}

}
