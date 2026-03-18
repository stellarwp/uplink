/**
 * Upsell section: products not covered by the current license.
 *
 * @package StellarWP\Uplink
 */
import { __ } from '@wordpress/i18n';
import { Rocket } from 'lucide-react';
import { SectionHeader } from '@/components/atoms/SectionHeader';
import { UpsellCard } from '@/components/molecules/UpsellCard';
import type { Product } from '@/types/api';

interface UpsellSectionProps {
    products:     Product[];
    upsellUrlMap: Record<string, string>;
}

/**
 * @since 3.0.0
 */
export function UpsellSection( { products, upsellUrlMap }: UpsellSectionProps ) {
    if ( products.length === 0 ) return null;

    return (
        <>
            <hr className="border-t border-0 !border-b-0" />

            <div className="space-y-3">
                <SectionHeader
                    icon={ <Rocket className="w-4 h-4 text-muted-foreground" /> }
                    label={ __( 'Add to your plan', '%TEXTDOMAIN%' ) }
                />
                <div className="space-y-2">
                    { products.map( ( p ) => (
                        <UpsellCard
                            key={ p.slug }
                            product={ p }
                            href={ upsellUrlMap[ p.slug ] ?? '#' }
                        />
                    ) ) }
                </div>
            </div>
        </>
    );
}
