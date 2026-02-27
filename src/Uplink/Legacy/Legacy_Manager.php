<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Legacy;

use StellarWP\Uplink\Resources\Collection;
use StellarWP\Uplink\Resources\Resource;

/**
 * Manages legacy license collection for a single Uplink instance's
 * resources.
 *
 * @since 3.0.0
 */
class Legacy_Manager {

	/**
	 * @var Collection
	 */
	protected $collection;

	/**
	 * @param Collection $collection
	 */
	public function __construct( Collection $collection ) {
		$this->collection = $collection;
	}

	/**
	 * Collect legacy licenses from all resources in this instance
	 * that have a license provider.
	 *
	 * @since 3.0.0
	 *
	 * @return Legacy_License[]
	 */
	public function collect_licenses(): array {
		$licenses = [];

		foreach ( $this->get_legacy_resources() as $resource ) {
			$config = $resource->get_legacy_config();

			if ( $config && $config->has_licenses() ) {
				$licenses = array_merge( $licenses, $config->get_licenses() );
			}
		}

		return $licenses;
	}

	/**
	 * Get all resources that have a legacy config.
	 *
	 * @since 3.0.0
	 *
	 * @return Resource[]
	 */
	protected function get_legacy_resources(): array {
		$resources = [];

		foreach ( $this->collection as $resource ) {
			/** @var Resource $resource */
			if ( $resource->has_legacy_config() ) {
				$resources[] = $resource;
			}
		}

		return $resources;
	}
}
