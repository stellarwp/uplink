/**
 * My Products tab content.
 *
 * No longer rendered — content was inlined into AppShell in Phase 1.
 * Kept here until cleanup is confirmed safe.
 *
 * @package StellarWP\Uplink
 */
import { LegacyLicenseBanner } from '@/components/molecules/LegacyLicenseBanner';
import { ProductSection } from '@/components/organisms/ProductSection';
import { PRODUCTS } from '@/data/products';

/**
 * @since 3.0.0
 */
export function MyProductsTab() {
    return (
        <div className="flex flex-col gap-4">
            <LegacyLicenseBanner />
            { PRODUCTS.map( ( product ) => (
                <ProductSection
                    key={ product.slug }
                    product={ product }
                />
            ) ) }
        </div>
    );
}
