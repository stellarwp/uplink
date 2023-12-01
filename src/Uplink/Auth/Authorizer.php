<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Auth;

use StellarWP\Uplink\Pipeline\Pipeline;
use StellarWP\Uplink\Resources\Resource;

/**
 * Determines if the current site will allow the user to use the authorize button.
 */
final class Authorizer {

	/**
	 * @var Pipeline
	 */
	private $pipeline;

	/**
	 * @param  Pipeline  $pipeline  The populated pipeline of a set of rules to authorize a user.
	 */
	public function __construct( Pipeline $pipeline ) {
		$this->pipeline = $pipeline;
	}

	/**
	 * Runs the pipeline which executes a series of checks to determine if
	 * the user can use the authorize button on the current site.
	 *
	 * @see Provider::register_authorizer()
	 *
	 * @param  Resource  $resource
	 *
	 * @return bool
	 */
	public function can_auth( Resource $resource ): bool {
		$authorized = new Authorized();
		$authorized->resource = $resource;

		$result = $this->pipeline->send( $authorized )->thenReturn();

		return $result->authorized;
	}

}
