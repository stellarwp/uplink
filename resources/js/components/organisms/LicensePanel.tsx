/**
 * License sidebar panel.
 *
 * Always visible. Fetches license and catalog data from the store and passes
 * it to LicenseSection and UpsellSection.
 *
 * @package StellarWP\Uplink
 */
import { useMemo } from 'react';
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { LicenseSection } from '@/components/organisms/LicenseSection';
import { UpsellSection } from '@/components/organisms/UpsellSection';
import { store as uplinkStore } from '@/store';
import { PRODUCTS } from '@/data/products';
import { useToast } from '@/context/toast-context';
import { UplinkError } from '@/errors';

/**
 * @since 3.0.0
 */
export function LicensePanel() {
    const { addToast }      = useToast();
    const { deleteLicense } = useDispatch( uplinkStore );

    const { licenseKey, licenseProducts, catalogs } = useSelect(
        ( select ) => ({
            licenseKey:      select( uplinkStore ).getLicenseKey(),
            licenseProducts: select( uplinkStore ).getLicenseProducts(),
            catalogs:        select( uplinkStore ).getCatalog(),
        }),
        []
    );

    // Flat tier slug → display name lookup from all catalog tiers.
    const tierNameMap = useMemo( () => {
        const map: Record<string, string> = {};
        catalogs.forEach( ( catalog ) => {
            catalog.tiers.forEach( ( t ) => {
                map[ t.slug ] = t.name;
            } );
        } );
        return map;
    }, [ catalogs ] );

    // Product slug → lowest-tier purchase URL map from the catalog.
    const upsellUrlMap = useMemo( () => {
        const map: Record<string, string> = {};
        catalogs.forEach( ( catalog ) => {
            const sorted = catalog.tiers.slice().sort( ( a, b ) => a.rank - b.rank );
            if ( sorted[ 0 ]?.purchase_url ) {
                map[ catalog.product_slug ] = sorted[ 0 ].purchase_url;
            }
        } );
        return map;
    }, [ catalogs ] );

    const licensedSlugs  = new Set( licenseProducts.map( ( lp ) => lp.product_slug ) );
    const upsellProducts = PRODUCTS.filter( ( p ) => ! licensedSlugs.has( p.slug ) );

    const handleRemove = async () => {
        const result = await deleteLicense();
        if ( result instanceof UplinkError ) {
            addToast( result.message, 'error' );
        } else {
            addToast( __( 'License removed.', '%TEXTDOMAIN%' ), 'default' );
        }
    };

    return (
        <div className="sticky top-4 w-[280px] shrink-0 space-y-6">
            <LicenseSection
                licenseKey={ licenseKey }
                licenseProducts={ licenseProducts }
                tierNameMap={ tierNameMap }
                onRemove={ handleRemove }
            />
            <UpsellSection
                products={ upsellProducts }
                upsellUrlMap={ upsellUrlMap }
            />
        </div>
    );
}
