/**
 * API type definitions for the License Manager Dashboard.
 *
 * @package StellarWP\Uplink
 */

// ---------------------------------------------------------------------------
// Legacy types (slug-based model — kept until old organisms are deleted)
// ---------------------------------------------------------------------------

/**
 * The license and activation state of a feature entry.
 * @since 3.0.0
 */
export type FeatureLicenseState = 'active' | 'inactive' | 'not_included';

/**
 * Activation status of the master license key itself.
 * @since 3.0.0
 */
export type LicenseStatus = 'active' | 'expired' | 'invalid' | 'idle';

/**
 * @since 3.0.0
 * @deprecated Use the new id-based Feature type from the new data model.
 */
export interface LegacyFeature {
    /** Unique feature slug (e.g. "give-recurring") */
    slug: string;
    /** Human-readable feature name */
    name: string;
    /** Short description shown below the feature name */
    description: string;
    /** Installed version string. Empty string when not installed or not_included. */
    version: string;
    /** License and activation state */
    licenseState: FeatureLicenseState;
    /** URL to download/install the feature zip (inactive features only). */
    downloadUrl?: string;
    /** URL to purchase or upgrade the license (not_included features only). */
    upgradeUrl?: string;
}


// ---------------------------------------------------------------------------
// New types (id-based model — design-team data model)
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
 * A feature belonging to a product tier.
 * @since 3.0.0
 */
export interface ProductFeature {
    /** Numeric feature ID */
    id: number;
    /** Human-readable feature name */
    name: string;
    /** Short description shown below the feature name */
    description: string;
    /** Minimum tier required to access this feature */
    requiredTier: TierSlug;
    /** Category/group for display grouping */
    category: string;
}

/**
 * A product with tiered plans and features.
 * @since 3.0.0
 */
export interface Product {
    /** Unique product slug (e.g. "givewp") */
    slug: string;
    /** Display name (e.g. "GiveWP") */
    name: string;
    /** Short tagline */
    tagline: string;
    /** Available tiers (ordered starter → pro → agency) */
    tiers: Tier[];
    /** All features across all tiers */
    features: ProductFeature[];
}

/**
 * A license key entry.
 * @since 3.0.0
 */
export interface License {
    /** The license key string */
    key: string;
    /**
     * License type:
     * - unified: covers all products at the licensed tier
     * - legacy: product-specific legacy key (shows amber banner)
     */
    type: 'unified' | 'legacy';
    /** Tier this license grants access to */
    tier: TierSlug;
    /** Products this license applies to (slug list) */
    productSlugs: string[];
    /** Human-readable expiry date (e.g. "December 31, 2026") */
    expires: string;
    /** Whether this license is currently expired */
    isExpired: boolean;
    /** Renewal URL */
    renewUrl: string;
}

/**
 * Per-product activation record stored in the license store.
 * @since 3.0.0
 */
export interface LicenseProduct {
    productSlug: string;
    tier: TierSlug;
    licenseKey: string;
}

/**
 * Runtime toggle state for an individual feature.
 * @since 3.0.0
 */
export interface FeatureState {
    featureId: number;
    productSlug: string;
    enabled: boolean;
}
