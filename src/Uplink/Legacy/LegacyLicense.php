<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Legacy;

/**
 * Represents a license key discovered from a plugin's legacy storage.
 *
 * @since 3.0.0
 */
class LegacyLicense {

	/**
	 * @var string
	 */
	public $key;

	/**
	 * @var string
	 */
	public $resource_slug;

	/**
	 * @var string
	 */
	public $status;

	/**
	 * @param string $key           The license key.
	 * @param string $resource_slug The Uplink resource slug this key belongs to.
	 * @param string $status        The license status (e.g. 'valid', 'expired', 'invalid').
	 */
	public function __construct( string $key, string $resource_slug, string $status = 'unknown' ) {
		$this->key           = $key;
		$this->resource_slug = $resource_slug;
		$this->status        = $status;
	}
}
