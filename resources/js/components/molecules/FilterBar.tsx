/**
 * Page header filter bar.
 *
 * Displays the brand logo, a feature search input, and a product filter
 * dropdown. Both inputs are wired to FilterContext via useFilter().
 *
 * @package StellarWP\Uplink
 */
import { __ } from '@wordpress/i18n';
import { Search } from 'lucide-react';
import { Input } from '@/components/ui/input';
import { Select, SelectTrigger, SelectValue, SelectContent, SelectItem } from '@/components/ui/select';
import { PRODUCTS } from '@/data/products';
import { useFilter } from '@/context/filter-context';
import logoLW from '@img/logo-lw-software.svg';

/**
 * @since 3.0.0
 */
export function FilterBar() {
    const { searchQuery, setSearchQuery, productFilter, setProductFilter } = useFilter();

    const handleProductChange = ( slug: string ) => {
        setProductFilter( slug );
        if ( slug !== 'all' ) {
            setSearchQuery( '' );
        }
    };

    return (
        <div className="flex flex-wrap items-center gap-3">
            <img
                src={ logoLW }
                alt={ __( 'Liquid Web Software', '%TEXTDOMAIN%' ) }
                className="w-[240px] shrink-0"
            />

            <div className="relative w-[260px]">
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground pointer-events-none" />
                <Input
                    value={ searchQuery }
                    onChange={ ( e ) => setSearchQuery( e.target.value ) }
                    placeholder={ __( 'Search features…', '%TEXTDOMAIN%' ) }
                    className="pl-10 text-sm"
                />
            </div>

            <Select value={ productFilter } onValueChange={ handleProductChange }>
                <SelectTrigger className="w-[168px]">
                    <SelectValue placeholder={ __( 'All Products', '%TEXTDOMAIN%' ) } />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">{ __( 'All Products', '%TEXTDOMAIN%' ) }</SelectItem>
                    { PRODUCTS.map( ( p ) => (
                        <SelectItem key={ p.slug } value={ p.slug }>{ p.name }</SelectItem>
                    ) ) }
                </SelectContent>
            </Select>
        </div>
    );
}
