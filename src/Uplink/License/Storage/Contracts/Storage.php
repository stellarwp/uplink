<?php declare( strict_types=1 );

namespace StellarWP\Uplink\License\Storage\Contracts;

use StellarWP\Uplink\Resources\Resource;

interface Storage {

	/**
	 * The License Key "key" prefix, combined with a Resource slug,
	 * will make up the option name.
	 */
	public const KEY_PREFIX = 'stellarwp_uplink_license_key_';

	/**
	 * Store the license key.
	 *
	 * @param  Resource  $resource The resource to associate with the license key.
	 * @param  string    $license_key The license key to store.
	 *
	 * @return bool
	 */
	public function store( Resource $resource, string $license_key ): bool;

	/**
	 * Retrieves a stored license key.
	 *
	 * @param  Resource  $resource The resources associated with the license key.
	 *
	 * @return string|null
	 */
	public function get( Resource $resource ): ?string;

	/**
	 * Deletes a license key.
	 *
	 * @param  Resource  $resource The resources associated with the license key.
	 *
	 * @return bool
	 */
	public function delete( Resource $resource ): bool;

}
