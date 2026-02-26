<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Legacy;

use StellarWP\Uplink\Resources\Collection;
use StellarWP\Uplink\Resources\Resource;

/**
 * Orchestrates legacy hook suppression across all registered
 * resources that have legacy configs.
 *
 * @since 3.1.0
 */
class LegacyManager {

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
	 * Suppress legacy hooks for all resources that registered them,
	 * but only when a unified key exists. Without a unified key the
	 * legacy systems should remain active.
	 *
	 * @since 3.1.0
	 *
	 * @return void
	 */
	public function suppress_all(): void {
		if ( ! UnifiedKey::exists() ) {
			return;
		}

		foreach ( $this->get_legacy_resources() as $resource ) {
			$config = $resource->get_legacy_config();

			if ( $config ) {
				$config->suppress();
			}
		}
	}

	/**
	 * Collect legacy licenses from all resources in this instance's
	 * collection that have a license provider.
	 *
	 * @since 3.1.0
	 *
	 * @return LegacyLicense[]
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
	 * @since 3.1.0
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
