/**
 * Selectors for the stellarwp/uplink @wordpress/data store.
 *
 * @package StellarWP\Uplink
 */
import { createSelector } from '@wordpress/data';
import type { State } from './types';
import type {
	CatalogTier,
	Feature,
	LicenseProduct,
	ProductCatalog,
} from '@/types/api';
import type UplinkError from '@/errors/uplink-error';

// ---------------------------------------------------------------------------
// Features
// ---------------------------------------------------------------------------

export const getFeatures = createSelector(
	(state: State): Feature[] => Object.values(state.features.bySlug),
	(state: State) => [state.features.bySlug]
);

export const getFeaturesByProduct = createSelector(
	(state: State, product: string): Feature[] =>
		Object.values(state.features.bySlug).filter((f) => f.product === product),
	(state: State, product: string) => [state.features.bySlug, product]
);

export const getFeature = (state: State, slug: string): Feature | null =>
	state.features.bySlug[slug] ?? null;

export const isFeatureEnabled = (state: State, slug: string): boolean =>
	state.features.bySlug[slug]?.is_enabled ?? false;

export const isFeatureToggling = (state: State, slug: string): boolean =>
	state.features.toggling[slug] ?? false;

export const getFeatureError = (
	state: State,
	slug: string
): UplinkError | null => state.features.errorBySlug[slug] ?? null;

export const isFeatureUpdating = (state: State, slug: string): boolean =>
	state.features.updating[slug] ?? false;

/**
 * True when any plugin or theme feature is being toggled or updated.
 *
 * Both toggle and update operations trigger WordPress install/activate/deactivate
 * operations that should not run concurrently. Flag features are exempt
 * because they only flip a database option.
 *
 * Memoized via createSelector so the loops only re-run when
 * the relevant sub-trees actually change.
 */
export const isAnyInstallableBusy = createSelector(
	(state: State): boolean => {
		const { toggling, updating, bySlug } = state.features;
		const isNonFlag = (slug: string): boolean => {
			const feature = bySlug[slug];
			return feature !== undefined && feature.type !== 'flag';
		};
		return (
			Object.keys(toggling).some(isNonFlag) ||
			Object.keys(updating).some(isNonFlag)
		);
	},
	(state: State) => [
		state.features.toggling,
		state.features.updating,
		state.features.bySlug,
	]
);

// ---------------------------------------------------------------------------
// Catalog
// ---------------------------------------------------------------------------

export const getCatalog = createSelector(
	(state: State): ProductCatalog[] =>
		Object.values(state.catalog.byProductSlug),
	(state: State) => [state.catalog.byProductSlug]
);

export const getProductCatalog = (
	state: State,
	slug: string
): ProductCatalog | null => state.catalog.byProductSlug[slug] ?? null;

export const getProductTiers = createSelector(
	(state: State, slug: string): CatalogTier[] =>
		state.catalog.byProductSlug[slug]?.tiers ?? [],
	(state: State, slug: string) => [state.catalog.byProductSlug, slug]
);

/**
 * Returns a single CatalogTier by product slug and tier slug, or null.
 *
 * CatalogTier.purchase_url is the authoritative source for upgrade links —
 * prefer it over the static Tier.upgradeUrl fixture when available.
 */
export const getCatalogTier = (
	state: State,
	productSlug: string,
	tierSlug: string
): CatalogTier | null =>
	state.catalog.byProductSlug[productSlug]?.tiers.find(
		(t) => t.slug === tierSlug
	) ?? null;

// ---------------------------------------------------------------------------
// License
// ---------------------------------------------------------------------------

/**
 * Returns the stored unified license key, or null. Triggers getLicenseKey resolver.
 * @param state
 */
export const getLicenseKey = (state: State): string | null =>
	state.license.license.key;

export const hasLicense = (state: State): boolean =>
	state.license.license.key !== null;

export const getLicenseProducts = (state: State): LicenseProduct[] =>
	state.license.license.products;

export const isLicenseStoring = (state: State): boolean =>
	state.license.isStoring;

export const isLicenseDeleting = (state: State): boolean =>
	state.license.isDeleting;

export const isProductValidating = (state: State): boolean =>
	state.license.isValidating;

export const canModifyLicense = (state: State): boolean =>
	!state.license.isStoring &&
	!state.license.isValidating &&
	!state.license.isDeleting;

export const getStoreLicenseError = (state: State): UplinkError | null =>
	state.license.storeError;

export const getDeleteLicenseError = (state: State): UplinkError | null =>
	state.license.deleteError;

export const getValidateProductError = (state: State): UplinkError | null =>
	state.license.validateError;
