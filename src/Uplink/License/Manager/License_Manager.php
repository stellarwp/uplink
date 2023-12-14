<?php declare( strict_types=1 );

namespace StellarWP\Uplink\License\Manager;

use StellarWP\Uplink\Config;
use StellarWP\Uplink\Pipeline\Pipeline;
use StellarWP\Uplink\Resources\Resource;

final class License_Manager {

	/**
	 * The multisite processing pipeline.
	 *
	 * @var Pipeline
	 */
	private $pipeline;

	/**
	 * Cached results for existing resources.
	 *
	 * @var array<string, bool>
	 */
	private $cache;

	/**
	 * Whether we allow memoization caching.
	 *
	 * @var bool
	 */
	private $cache_enabled = true;

	/**
	 * @param  Pipeline  $pipeline
	 */
	public function __construct( Pipeline $pipeline ) {
		$this->pipeline = $pipeline;
	}

	/**
	 * Check if the current multisite and Uplink configuration allows a multisite
	 * license for the current subsite.
	 *
	 * Out of the box, sub-sites act independently of the network.
	 *
	 * @see Config::allow_site_level_licenses_for_subfolder_multisite()
	 * @see Config::allow_site_level_licenses_for_subdomain_multisite()
	 * @see Config::allow_site_level_licenses_for_mapped_domain_multisite()
	 *
	 * @param  Resource  $resource The current resource to check against.
	 *
	 * @return bool
	 */
	public function allows_multisite_license( Resource $resource ): bool {
		$key = $resource->get_slug();

		if ( $this->cache_enabled ) {
			$cache = $this->cache[ $key ] ?? null;

			if ( $cache !== null ) {
				return $cache;
			}
		}

		// We're on single site or, the plugin isn't network activated.
		if ( ! is_multisite() || ! $resource->is_network_activated() ) {
			return $this->cache[ $key ] = false;
		}

		return $this->cache[ $key ] = $this->pipeline->send( false )->thenReturn();
	}

	/**
	 * Disable memoization cache, useful for automated tests.
	 */
	public function disable_cache(): void {
		$this->cache_enabled = false;
	}

}
