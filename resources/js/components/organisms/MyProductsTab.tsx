/**
 * My Products tab content.
 *
 * Always shows all products. Licensed products show feature toggles;
 * unlicensed products show features in a not-licensed state with CTAs.
 *
 * @package StellarWP\Uplink
 */
import { LegacyLicenseBanner } from '@/components/molecules/LegacyLicenseBanner';
import { ProductSection } from '@/components/organisms/ProductSection';
import { PRODUCTS } from '@/data/products';

interface MyProductsTabProps {
    /** Opens the Add License dialog (wired up in AppShell) */
    onAddLicense: () => void;
}

/**
 * @since 3.0.0
 */
export function MyProductsTab( { onAddLicense }: MyProductsTabProps ) {
    return (
        <div className="flex flex-col gap-4">
            <LegacyLicenseBanner />
            { PRODUCTS.map( ( product ) => (
                <ProductSection
                    key={ product.slug }
                    product={ product }
                    onAddLicense={ onAddLicense }
                />
            ) ) }
        </div>
    );
}
