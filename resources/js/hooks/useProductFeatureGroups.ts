/**
 * Partitions features for a product into available and locked groups,
 * and groups locked features by catalog tier.
 *
 * @package StellarWP\Uplink
 */
import { useSelect } from '@wordpress/data';
import { useFilteredFeatures } from '@/hooks/useFilteredFeatures';
import { store as uplinkStore } from '@/store';
import type { CatalogTier, Feature } from '@/types/api';

interface FeatureGroups {
    availableFeatures:  Feature[];
    lockedByTier:       Record<string, Feature[]>;
    sortedCatalogTiers: CatalogTier[];
}

/**
 * @since 3.0.0
 */
export function useProductFeatureGroups( productSlug: string ): FeatureGroups {
    const allFeatures = useFilteredFeatures( productSlug );

    const catalogTiers = useSelect(
        ( select ) => select( uplinkStore ).getProductCatalog( productSlug )?.tiers ?? [],
        [ productSlug ]
    );

    const isFreeFeature = ( f: Feature ) => ! f.tier || f.tier.toLowerCase().includes( 'free' );

    const availableFeatures = allFeatures.filter( ( f ) => f.is_available || isFreeFeature( f ) );
    const lockedFeatures    = allFeatures.filter( ( f ) => ! f.is_available && ! isFreeFeature( f ) );

    const sortedCatalogTiers = catalogTiers.slice().sort( ( a, b ) => a.rank - b.rank );

    const lockedByTier = sortedCatalogTiers.reduce<Record<string, Feature[]>>(
        ( acc, tier ) => {
            acc[ tier.slug ] = lockedFeatures.filter( ( f ) => f.tier === tier.slug );
            return acc;
        },
        {}
    );

    return { availableFeatures, lockedByTier, sortedCatalogTiers };
}
