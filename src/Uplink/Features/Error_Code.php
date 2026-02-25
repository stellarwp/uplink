<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features;

/**
 * WP_Error codes for the Features system.
 *
 * PHP 7.4 does not support native enums, so string
 * constants serve as the next-best compile-time guard.
 *
 * @since 3.0.0
 */
class Error_Code {

	/**
	 * A requested feature was not found in the catalog.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const FEATURE_NOT_FOUND = 'stellarwp-uplink-feature-not-found';

	/**
	 * A feature check failed due to an unexpected error.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const FEATURE_CHECK_FAILED = 'stellarwp-uplink-feature-check-failed';
}
