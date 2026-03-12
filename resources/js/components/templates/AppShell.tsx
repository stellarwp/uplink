/**
 * Application shell — full-width two-column layout.
 *
 * Main area: FilterBar header + product sections.
 * Sidebar: license panel.
 *
 * @package StellarWP\Uplink
 */
import { __ } from '@wordpress/i18n';
import { Loader2 } from 'lucide-react';
import { useSelect } from '@wordpress/data';
import { Shell } from '@/components/templates/Shell';
import { FilterBar } from '@/components/molecules/FilterBar';
import { LicensePanel } from '@/components/organisms/LicensePanel';
import { LegacyLicenseBanner } from '@/components/molecules/LegacyLicenseBanner';
import { ProductSection } from '@/components/organisms/ProductSection';
import { ErrorBoundary } from '@/components/ErrorBoundary';
import { PRODUCTS } from '@/data/products';
import { store as uplinkStore } from '@/store';
import { useFilter } from '@/context/filter-context';

/**
 * @since 3.0.0
 */
export function AppShell() {
    // Trigger both resolvers and wait for them to complete before rendering
    // content, so we never flash a "No license" state on first load.
    const isLoading = useSelect(
        ( select ) => {
            const s = select( uplinkStore ) as unknown as {
                hasFinishedResolution: ( name: string, args?: unknown[] ) => boolean;
            };
            select( uplinkStore ).getLicenseKey();
            select( uplinkStore ).getFeatures();
            return ! s.hasFinishedResolution( 'getLicenseKey', [] )
                || ! s.hasFinishedResolution( 'getFeatures', [] );
        },
        [],
    );

    const { productFilter } = useFilter();

    const visibleProducts = productFilter === 'all'
        ? PRODUCTS
        : PRODUCTS.filter( ( p ) => p.slug === productFilter );

    return (
        <Shell
            header={ <FilterBar /> }
            sideContent={ <LicensePanel /> }
        >
            { isLoading ? (
                <div className="flex items-center justify-center gap-2 py-16 text-sm text-muted-foreground">
                    <Loader2 className="w-5 h-5 animate-spin" />
                    { __( 'Loading…', '%TEXTDOMAIN%' ) }
                </div>
            ) : (
                <ErrorBoundary>
                    <div className="space-y-8">
                        <LegacyLicenseBanner />

						<div className="flex items-center !mt-8 !mb-6">
							<h2 className="!text-2xl !font-normal !m-0 !p-0">{ __( 'Your Features', '%TEXTDOMAIN%' ) }</h2>
						</div>

                        { visibleProducts.map( ( product ) => (
                            <ProductSection
                                key={ product.slug }
                                product={ product }
                            />
                        ) ) }
                    </div>
                </ErrorBoundary>
            ) }
        </Shell>
    );
}
