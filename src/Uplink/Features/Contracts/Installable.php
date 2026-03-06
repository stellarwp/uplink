<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Contracts;

/**
 * Contract for feature types that can be installed as WordPress extensions.
 *
 * Implemented by Plugin and Theme — not by Built_In.
 * Provides a uniform surface for Installable_Strategy template methods.
 *
 * @since 3.0.0
 */
interface Installable {

	/**
	 * Gets the expected extension authors for ownership verification.
	 *
	 * @since 3.0.0
	 *
	 * @return string[]
	 */
	public function get_authors(): array;

	/**
	 * Whether this extension is available on WordPress.org.
	 *
	 * Prepares for future install-path branching (download_url vs .org repository).
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public function is_dot_org(): bool;
}
