/**
 * Hook that returns features for a product group filtered by the active
 * search query from FilterContext.
 *
 * When the search query is empty the original selector result is returned
 * directly, so ProductSection only re-renders when features or the query
 * actually change.
 *
 * @package StellarWP\Uplink
 */
import { useSelect } from '@wordpress/data';
import { useFilter } from '@/context/filter-context';
import { store as uplinkStore } from '@/store';
import type { Feature } from '@/types/api';

/**
 * @since 3.0.0
 */
export function useFilteredFeatures( productSlug: string ): Feature[] {
    const { searchQuery } = useFilter();

    const features = useSelect(
        ( select ) => select( uplinkStore ).getFeaturesByGroup( productSlug ),
        [ productSlug ],
    );

    const query = searchQuery.trim().toLowerCase();

    if ( ! query ) return features;

    return features.filter(
        ( f ) =>
            f.name.toLowerCase().includes( query ) ||
            f.description.toLowerCase().includes( query ),
    );
}
