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
	 * @var string
	 */
	public $license_page_url;

	/**
	 * @param string $key              The license key.
	 * @param string $resource_slug    The Uplink resource slug this key belongs to.
	 * @param string $status           The license status (e.g. 'valid', 'expired', 'invalid').
	 * @param string $license_page_url URL to the plugin's legacy license management page.
	 */
	public function __construct( string $key, string $resource_slug, string $status = 'unknown', string $license_page_url = '' ) {
		$this->key              = $key;
		$this->resource_slug    = $resource_slug;
		$this->status           = $status;
		$this->license_page_url = $license_page_url;
	}
}
