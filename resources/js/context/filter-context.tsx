/**
 * Filter context — shared search query and product filter state.
 *
 * Mount <FilterProvider> once in App.tsx; consume with useFilter() anywhere
 * in the component tree.
 *
 * @package StellarWP\Uplink
 */
import { createContext, useContext, useState, type ReactNode } from 'react';

interface FilterContextValue {
    searchQuery:      string;
    productFilter:    string;
    setSearchQuery:   ( q: string ) => void;
    setProductFilter: ( slug: string ) => void;
}

const FilterContext = createContext<FilterContextValue>( {
    searchQuery:      '',
    productFilter:    'all',
    setSearchQuery:   () => {},
    setProductFilter: () => {},
} );

/**
 * @since 3.0.0
 */
export function FilterProvider( { children }: { children: ReactNode } ) {
    const [ searchQuery, setSearchQuery ]     = useState( '' );
    const [ productFilter, setProductFilter ] = useState( 'all' );

    return (
        <FilterContext.Provider value={ { searchQuery, setSearchQuery, productFilter, setProductFilter } }>
            { children }
        </FilterContext.Provider>
    );
}

/**
 * @since 3.0.0
 */
export const useFilter = () => useContext( FilterContext );
