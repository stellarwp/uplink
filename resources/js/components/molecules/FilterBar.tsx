/**
 * Page header filter bar.
 *
 * Displays the brand logo, a feature search input, and a product filter
 * dropdown. Search and filter functionality is deferred — the inputs are
 * rendered but state wiring is not implemented in this phase.
 *
 * @package StellarWP\Uplink
 */
import { __ } from '@wordpress/i18n';
import { Search } from 'lucide-react';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { PRODUCTS } from '@/data/products';
import logoLW from '@img/logo-lw-software.svg';

/**
 * @since 3.0.0
 */
export function FilterBar() {
    return (
        <div className="flex flex-wrap items-center gap-3 py-4">
            <img
                src={ logoLW }
                alt={ __( 'Liquid Web Software', '%TEXTDOMAIN%' ) }
                className="w-[240px] shrink-0"
            />

            <div className="relative w-[260px]">
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground pointer-events-none" />
                <Input
                    placeholder={ __( 'Search features…', '%TEXTDOMAIN%' ) }
                    className="pl-10 text-sm"
                    disabled
                />
            </div>

            <Select className="w-[168px]" disabled>
                <option value="all">{ __( 'All Products', '%TEXTDOMAIN%' ) }</option>
                { PRODUCTS.map( ( p ) => (
                    <option key={ p.slug } value={ p.slug }>{ p.name }</option>
                ) ) }
            </Select>
        </div>
    );
}
