/**
 * API type definitions for the License Manager Dashboard.
 *
 * These mirror what the uplink/v1 REST API will return.
 * Currently backed by resources/js/data/mock-features.json.
 *
 * @package StellarWP\Uplink
 */

/**
 * The license and activation state of a feature entry.
 *
 * - active:       Licensed and the WP feature is active.
 * - inactive:     Licensed but the WP feature is either not activated or not yet
 *                 installed. When downloadUrl is present, toggling to active
 *                 triggers programmatic installation then activation via the REST API.
 * - not_included: Not part of the user's plan. Row is locked with an upsell.
 *
 * @since TBD
 */
export type FeatureLicenseState = 'active' | 'inactive' | 'not_included';

/**
 * Activation status of the master license key itself.
 *
 * @since TBD
 */
export type LicenseStatus = 'active' | 'expired' | 'invalid' | 'idle';

/**
 * @since TBD
 */
export interface Feature {
    /** Unique feature slug (e.g. "give-recurring") */
    slug: string;
    /** Human-readable feature name */
    name: string;
    /** Short description shown below the feature name */
    description: string;
    /** Installed version string (e.g. "2.4.1"). Empty string when not installed or not_included. */
    version: string;
    /** License and activation state */
    licenseState: FeatureLicenseState;
    /**
     * URL to download and install the feature zip.
     * Only present for inactive features that are not yet installed on this WordPress site.
     * When toggling to active, the REST API uses this URL to install the feature first.
     */
    downloadUrl?: string;
    /**
     * URL to purchase or upgrade the license.
     * Only present when licenseState === 'not_included'.
     */
    upgradeUrl?: string;
}

/**
 * @since TBD
 */
export interface Brand {
    /** Unique brand slug (e.g. "givewp"). Used to look up BrandConfig. */
    slug: string;
    /** Display name (e.g. "GiveWP") */
    name: string;
    /** Short tagline shown below the brand name */
    tagline: string;
    /** Features belonging to this brand */
    features: Feature[];
}

/**
 * @since TBD
 */
export interface LicenseData {
    /** The license key value */
    key: string;
    /** Registered email address */
    email: string;
    /** Current license status */
    status: LicenseStatus;
    /** Human-readable expiry date (e.g. "December 31, 2025") */
    expires: string;
}

/**
 * Root data shape returned by the mock JSON and eventually by the REST API.
 *
 * @since TBD
 */
export interface DashboardData {
    license: LicenseData;
    brands: Brand[];
}
