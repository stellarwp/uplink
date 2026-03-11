<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Contracts;

use StellarWP\Uplink\Catalog\Results\Catalog_Feature;

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

	/**
	 * Builds the complete update data array for this feature type.
	 *
	 * Each type includes common fields plus type-specific fields (e.g. plugin_file,
	 * installed_version) so the handler does not need an extra feature lookup.
	 *
	 * @since 3.0.0
	 *
	 * @param Catalog_Feature $catalog_feature The catalog entry providing version and download URL.
	 *
	 * @return array<string, mixed>
	 */
	public function get_update_data( Catalog_Feature $catalog_feature ): array;
}
