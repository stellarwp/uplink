<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Auth\License\Pipeline\Processors;

use Closure;
use StellarWP\Uplink\Auth\Token\Managers\Network_Token_Manager;

final class Multisite_Token {

	/**
	 * @var Network_Token_Manager
	 */
	private $token_manager;

	/**
	 * @param  Network_Token_Manager  $token_manager The Token Manager Factory.
	 */
	public function __construct( Network_Token_Manager $token_manager ) {
		$this->token_manager = $token_manager;
	}

	/**
	 * Checks if a network already has a token and prevents a subsite from authorizing
	 * against that.
	 *
	 * @param  bool $is_multisite_license
	 * @param  Closure  $next
	 *
	 * @throws \RuntimeException
	 *
	 * @return bool
	 */
	public function __invoke( bool $is_multisite_license, Closure $next ): bool {
		// No token exists at the network level, allow this to proceed.
		if ( ! $this->token_manager->get() ) {
			return true;
		}

		return $next( $is_multisite_license );
	}

}
