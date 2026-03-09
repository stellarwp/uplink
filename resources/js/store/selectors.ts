/**
 * Selectors for the stellarwp/uplink @wordpress/data store.
 *
 * @package StellarWP\Uplink
 */
import { createSelector } from '@wordpress/data';
import type { State } from './types';
import type { CatalogTier, Feature, LicenseProduct, ProductCatalog } from '@/types/api';
import type UplinkError from '@/errors/uplink-error';

// ---------------------------------------------------------------------------
// Features
// ---------------------------------------------------------------------------

export const getFeatures = createSelector(
	(state: State): Feature[] => Object.values(state.features.bySlug),
	(state: State) => [state.features.bySlug]
);

export const getFeaturesByGroup = createSelector(
	(state: State, group: string): Feature[] =>
		Object.values(state.features.bySlug).filter((f) => f.group === group),
	(state: State, group: string) => [state.features.bySlug, group]
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

/**
 * True when any plugin or theme feature is being toggled.
 *
 * Plugin and theme toggles trigger WordPress install/activate/deactivate
 * operations that should not run concurrently. Flag features are exempt
 * because they only flip a database option.
 *
 * Memoized via createSelector so the `.some()` loop only re-runs when
 * the `toggling` or `bySlug` sub-trees actually change.
 */
export const isAnyInstallableToggling = createSelector(
	(state: State): boolean =>
		Object.keys(state.features.toggling).some((slug) => {
			const feature = state.features.bySlug[slug];
			return feature !== undefined && feature.type !== 'flag';
		}),
	(state: State) => [state.features.toggling, state.features.bySlug]
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

// ---------------------------------------------------------------------------
// License
// ---------------------------------------------------------------------------

/**
 * Returns the stored unified license key, or null. Triggers getLicense resolver.
 */
export const getLicense = (state: State): string | null => state.license.key;

export const hasLicense = (state: State): boolean =>
	state.license.key !== null;

export const getLicenseProducts = (state: State): LicenseProduct[] =>
	state.license.products;

export const isLicenseStoring = (state: State): boolean =>
	state.license.isStoring;

export const isLicenseDeleting = (state: State): boolean =>
	state.license.isDeleting;

export const isProductValidating = (state: State): boolean =>
	state.license.isValidating;

export const canModifyLicense = (state: State): boolean =>
	!state.license.isStoring && !state.license.isValidating && !state.license.isDeleting;

export const getStoreLicenseError = (state: State): UplinkError | null =>
	state.license.storeError;

export const getDeleteLicenseError = (state: State): UplinkError | null =>
	state.license.deleteError;

export const getValidateProductError = (state: State): UplinkError | null =>
	state.license.validateError;
