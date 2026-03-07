/**
 * API type definitions for the License Manager Dashboard.
 *
 * @package StellarWP\Uplink
 */

// ---------------------------------------------------------------------------
// REST API types — stellarwp/uplink/v1
// ---------------------------------------------------------------------------

/**
 * Feature type identifier returned by the REST API.
 * @since 3.0.0
 */
export type FeatureType = 'plugin' | 'flag';

/**
 * A feature as returned by GET /stellarwp/uplink/v1/features.
 *
 * Field names match the PHP Feature_Controller REST response exactly.
 * `plugin_file` is only present on plugin-type features.
 *
 * @since 3.0.0
 */
export interface Feature {
    /** Unique feature slug (e.g. "give-recurring-donations") */
    slug:              string;
    /** Human-readable feature name */
    name:              string;
    /** Short description */
    description:       string;
    /** Product group slug this feature belongs to (e.g. "give") */
    group:             string;
    /** Minimum tier required to access this feature */
    tier:              TierSlug;
    /** Feature delivery type */
    type:              FeatureType;
    /** Whether the feature is available on this site */
    is_available:      boolean;
    /** URL to documentation or learn-more page */
    documentation_url: string;
    /** Whether the feature is currently enabled (persisted server-side) */
    is_enabled:        boolean;
    /** Plugin file path relative to the plugins directory (zip type only) */
    plugin_file?:      string;
}

// ---------------------------------------------------------------------------
// Tier / product types
// ---------------------------------------------------------------------------

/**
 * Plan tier for a product.
 * @since 3.0.0
 */
export type TierSlug = 'starter' | 'pro' | 'agency';

/**
 * A plan tier definition.
 * @since 3.0.0
 */
export interface Tier {
    slug: TierSlug;
    /** Display name (e.g. "Pro") */
    name: string;
    /** Marketing description */
    description: string;
    /** Upgrade URL for purchasing this tier */
    upgradeUrl: string;
}

/**
 * A product with tiered plans.
 * @since 3.0.0
 */
export interface Product {
    /** Unique product slug — matches feature group field (e.g. "give", "kadence") */
    slug: string;
    /** Display name (e.g. "GiveWP") */
    name: string;
    /** Short tagline */
    tagline: string;
    /** Available tiers (ordered starter → pro → agency) */
    tiers: Tier[];
}

/**
 * Unified license key as returned by GET/POST /stellarwp/uplink/v1/license.
 * @since 3.0.0
 */
export interface License {
    /** The stored unified license key */
    key: string;
}

