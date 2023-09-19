<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Auth;

use StellarWP\Uplink\Pipeline\Pipeline;

/**
 * Determines if the current site will allow the user to use the authorize button.
 */
final class Authorizer {

	/**
	 * @var Pipeline
	 */
	private $pipeline;

	public function __construct( Pipeline $pipeline ) {
		$this->pipeline = $pipeline;
	}

	/**
	 * Runs the pipeline which executes a series of checks to determine if
	 * the user can use the authorize button on the current site.
	 *
	 * @see Provider::register_authorizer()
	 *
	 * @return bool
	 */
	public function can_auth(): bool {
		return $this->pipeline->send( true )->thenReturn();
	}

}
