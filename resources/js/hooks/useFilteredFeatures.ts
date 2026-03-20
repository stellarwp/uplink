/**
 * Hook that returns features for a product filtered by the active
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
        ( select ) => select( uplinkStore ).getFeaturesByProduct( productSlug ),
        [ productSlug ],
    );

    const query = searchQuery.trim();

    if ( ! query ) return features;

    // Try to use the query as a regex; fall back to a literal match if invalid.
    let pattern: RegExp;
    try {
        pattern = new RegExp( query, 'i' );
    } catch {
        pattern = new RegExp( query.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' ), 'i' );
    }

    return features.filter(
        ( f ) =>
            pattern.test( f.name ) ||
            pattern.test( f.slug ) ||
            pattern.test( f.description ),
    );
}
