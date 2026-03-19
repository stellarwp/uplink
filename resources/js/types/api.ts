/**
 * API type definitions for the License Manager Dashboard.
 *
 * @package StellarWP\Uplink
 */

// ---------------------------------------------------------------------------
// Feature types — GET /stellarwp/uplink/v1/features
// ---------------------------------------------------------------------------

/**
 * Feature type identifier returned by the REST API.
 *
 * @since 3.0.0
 */
export type FeatureType = 'plugin' | 'theme' | 'flag';

/**
 * Base properties shared by all feature types.
 *
 * @since 3.0.0
 */
interface BaseFeature {
    /**
     * Unique feature slug (e.g. "give-recurring-donations").
     */
    slug: string;
    /**
     * Human-readable feature name.
     */
    name: string;
    /**
     * Short description.
     */
    description: string;
    /**
     * Product slug this feature belongs to (e.g. "give").
     */
    product: string;
    /**
     * Minimum tier slug required to access this feature, or null for free features.
     */
    tier: string | null;
    /**
     * Whether the feature is available on this site.
     */
    is_available: boolean;
    /**
     * URL to documentation or learn-more page.
     */
    documentation_url: string;
    /**
     * Whether the feature is currently enabled (persisted server-side).
     */
    is_enabled: boolean;
    /**
     * Latest available version string, if known.
     */
    version?: string;
    /**
     * Installed version string, if known.
     */
    installed_version?: string;
    /**
     * Whether a newer version is available and the feature is currently installed.
     * Only present for installable features (plugin/theme).
     */
    has_update?: boolean;
}

/**
 * A feature delivered as a standalone WordPress plugin.
 *
 * @since 3.0.0
 */
export interface PluginFeature extends BaseFeature {
    type: 'plugin';
    /**
     * Plugin file path relative to the plugins directory.
     */
    plugin_file: string;
    /**
     * Slug used for plugins_api() lookups.
     */
    plugin_slug: string;
    /**
     * Expected plugin authors for ownership verification.
     */
    authors: string[];
    /**
     * Whether the plugin is hosted on WordPress.org.
     */
    is_dot_org: boolean;
}

/**
 * A feature delivered as a WordPress theme.
 *
 * @since 3.0.0
 */
export interface ThemeFeature extends BaseFeature {
    type: 'theme';
    /**
     * Expected theme authors for ownership verification.
     */
    authors: string[];
    /**
     * Whether the theme is hosted on WordPress.org.
     */
    is_dot_org: boolean;
}

/**
 * A feature gated by a database option flag.
 *
 * @since 3.0.0
 */
export interface FlagFeature extends BaseFeature {
    type: 'flag';
}

/**
 * Discriminated union of all feature types as returned by the REST API.
 *
 * @since 3.0.0
 */
export type Feature = PluginFeature | ThemeFeature | FlagFeature;

/**
 * Plugin and theme features that trigger WordPress install/activate/deactivate
 * operations. Flag features are excluded because they only flip a DB option.
 *
 * @since 3.0.0
 */
export type InstallableFeature = PluginFeature | ThemeFeature;

// ---------------------------------------------------------------------------
// Catalog types — GET /stellarwp/uplink/v1/catalog
// ---------------------------------------------------------------------------

/**
 * A raw catalog feature entry before feature resolution.
 *
 * Field names match the Catalog_Feature PHP class and the catalog REST
 * endpoint response. These differ from the resolved Feature types above
 * (e.g. feature_slug vs slug, minimum_tier vs tier).
 *
 * @since 3.0.0
 */
export interface CatalogFeature {
    /**
     * Unique feature slug.
     */
    feature_slug: string;
    /**
     * Feature delivery type.
     */
    type: FeatureType;
    /**
     * Minimum tier slug required for this feature.
     */
    minimum_tier: string;
    /**
     * Plugin file path (plugin type only), or null for non-plugin features.
     */
    plugin_file: string | null;
    /**
     * Whether the feature is hosted on WordPress.org.
     */
    is_dot_org: boolean;
    /**
     * Download URL for the feature archive, or null when unavailable.
     */
    download_url: string | null;
    /**
     * Latest version string, or null when unavailable.
     */
    version: string | null;
    /**
     * Human-readable feature name.
     */
    name: string;
    /**
     * Short description.
     */
    description: string;
    /**
     * Feature category within the product.
     */
    category: string;
    /**
     * Feature authors, or null when not applicable.
     */
    authors: string[] | null;
    /**
     * URL to documentation or learn-more page.
     */
    documentation_url: string;
}

/**
 * A tier entry from the product catalog.
 *
 * @since 3.0.0
 */
export interface CatalogTier {
    /**
     * Tier slug identifier.
     */
    slug: string;
    /**
     * Display name (e.g. "Pro").
     */
    name: string;
    /**
     * Numeric rank for tier comparison.
     */
    rank: number;
    /**
     * URL to purchase this tier.
     */
    purchase_url: string;
}

/**
 * A product catalog entry as returned by GET /stellarwp/uplink/v1/catalog.
 *
 * @since 3.0.0
 */
export interface ProductCatalog {
    /**
     * Product slug identifier.
     */
    product_slug: string;
    /**
     * Available tiers ordered by rank.
     */
    tiers: CatalogTier[];
    /**
     * Raw catalog features for this product.
     */
    features: CatalogFeature[];
}

// ---------------------------------------------------------------------------
// Product types (display layer)
// ---------------------------------------------------------------------------

/**
 * A product with tiered plans.
 *
 * Tier definitions come from the catalog API (CatalogTier[]) — not stored here.
 *
 * @since 3.0.0
 */
export interface Product {
    /**
     * Unique product slug, matches feature product field (e.g. "give", "kadence").
     */
    slug: string;
    /**
     * Display name (e.g. "GiveWP").
     */
    name: string;
    /**
     * Short tagline.
     */
    tagline: string;
}

// ---------------------------------------------------------------------------
// Legacy license types — GET /stellarwp/uplink/v1/legacy-licenses
// ---------------------------------------------------------------------------

/**
 * A legacy per-plugin license as returned by the legacy-licenses endpoint.
 *
 * @since 3.0.0
 */
export interface LegacyLicense {
	key: string;
	slug: string;
	name: string;
	product: string;
	is_active: boolean;
	page_url: string;
	expires_at: string;
}

// ---------------------------------------------------------------------------
// License types — GET/POST /stellarwp/uplink/v1/license
// ---------------------------------------------------------------------------

/**
 * A licensed product entry as returned alongside the license key.
 *
 * @since 3.0.0
 */
export interface LicenseProduct {
    product_slug: string;
    tier: string;
    pending_tier: string | null;
    status: string;
    expires: string;
    activations: {
        site_limit: number;
        active_count: number;
        over_limit: boolean;
    };
    installed_here?: boolean;
    validation_status?: string;
    is_valid?: boolean;
}

/**
 * Unified license key as returned by GET/POST /stellarwp/uplink/v1/license.
 *
 * @since 3.0.0
 */
export interface License {
    /**
     * The stored unified license key, or null if none is set.
     */
    key: string | null;
    /**
     * Licensed products associated with this key.
     */
    products: LicenseProduct[];
}
