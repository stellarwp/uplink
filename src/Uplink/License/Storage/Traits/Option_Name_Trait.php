<?php declare( strict_types=1 );

namespace StellarWP\Uplink\License\Storage\Traits;

use StellarWP\Uplink\License\Storage\Contracts\Storage;
use StellarWP\Uplink\Resources\Resource;

/**
 * @mixin Storage
 */
trait Option_Name_Trait {

	/**
	 * Get the unique option name to save in options/site options table for the
	 * current site.
	 *
	 * @param  Resource  $resource
	 *
	 * @return string
	 */
	public static function option_name( Resource $resource ): string {
		return self::KEY_PREFIX . $resource->get_slug();
	}

}
