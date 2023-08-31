<?php

namespace StellarWP\Uplink\Utils;

class Sanitize {
	/**
	 * Sanitizes a key.
	 *
	 * @since 1.2.2
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	public static function key( $key ) {
		return str_replace( [ '`', '"', "'" ], '', $key );
	}
}
