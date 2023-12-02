<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Auth;

use StellarWP\Uplink\Resources\Resource;

/**
 * Passed through the Pipeline to check if a user is authorized to
 * perform token actions.
 *
 * @see Authorizer::can_auth()
 */
final class Authorized {

	/**
	 * The current state of the authorization being checked.
	 *
	 * @var bool
	 */
	public $authorized = true;

	/**
	 * The Plugin/Service to check against.
	 *
	 * @var Resource
	 */
	private $resource;

	/**
	 * @param  Resource  $resource The Plugin/Service to check against.
	 */
	public function __construct( Resource $resource ) {
		$this->resource = $resource;
	}

	/**
	 * Return the current resource being checked.
	 *
	 * @return Resource
	 */
	public function resource(): Resource {
		return $this->resource;
	}

}
