/**
 * My Products tab content.
 *
 * Shows LegacyLicenseBanner + one ProductSection per licensed product,
 * or an empty state when no licenses are active.
 *
 * @package StellarWP\Uplink
 */
import { __ } from '@wordpress/i18n';
import { PackageOpen } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { LegacyLicenseBanner } from '@/components/molecules/LegacyLicenseBanner';
import { ProductSection } from '@/components/organisms/ProductSection';
import { useLicenseStore } from '@/stores/license-store';
import { PRODUCTS } from '@/data/products';

interface MyProductsTabProps {
    /** Opens the Add License dialog (wired up in AppShell) */
    onAddLicense: () => void;
}

/**
 * @since TBD
 */
export function MyProductsTab( { onAddLicense }: MyProductsTabProps ) {
    const activeLicenses = useLicenseStore( ( s ) => s.activeLicenses );

    // Determine which product slugs have at least one active license
    const licensedSlugs = new Set(
        activeLicenses.flatMap( ( l ) => l.productSlugs )
    );

    const licensedProducts = PRODUCTS.filter( ( p ) => licensedSlugs.has( p.slug ) );

    if ( licensedProducts.length === 0 ) {
        return (
            <div className="flex flex-col items-center justify-center py-16">
                <Card className="max-w-sm w-full">
                    <CardContent className="flex flex-col items-center gap-4 pt-8 pb-8 text-center">
                        <PackageOpen className="w-12 h-12 text-muted-foreground" />
                        <div>
                            <h3 className="font-semibold text-foreground">
                                { __( 'No active licenses', '%TEXTDOMAIN%' ) }
                            </h3>
                            <p className="text-sm text-muted-foreground mt-1">
                                { __( 'Add a license key to unlock features for your products.', '%TEXTDOMAIN%' ) }
                            </p>
                        </div>
                        <Button onClick={ onAddLicense }>
                            { __( 'Add License', '%TEXTDOMAIN%' ) }
                        </Button>
                    </CardContent>
                </Card>
            </div>
        );
    }

    return (
        <div className="flex flex-col gap-4">
            <LegacyLicenseBanner />
            { licensedProducts.map( ( product ) => (
                <ProductSection key={ product.slug } product={ product } />
            ) ) }
        </div>
    );
}
