/**
 * Application shell — full-width two-column layout.
 *
 * Main area: product sections.
 * Sidebar: license panel (wired in Phase 5).
 *
 * @package StellarWP\Uplink
 */
import { __ } from '@wordpress/i18n';
import { Cloud, Loader2 } from 'lucide-react';
import { useSelect } from '@wordpress/data';
import { Shell } from '@/components/templates/Shell';
import { LicensePanel } from '@/components/organisms/LicensePanel';
import { LegacyLicenseBanner } from '@/components/molecules/LegacyLicenseBanner';
import { ProductSection } from '@/components/organisms/ProductSection';
import { ErrorBoundary } from '@/components/ErrorBoundary';
import { PRODUCTS } from '@/data/products';
import { store as uplinkStore } from '@/store';

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

    return (
        <Shell sideContent={ <LicensePanel /> }>
            {/* Page Header — replaced by FilterBar in Phase 6 */}
            <div className="flex items-center gap-3 py-4">
                <div className="flex items-center justify-center w-10 h-10 rounded-lg bg-primary text-primary-foreground">
                    <Cloud className="w-6 h-6" />
                </div>
                <div>
                    <h1 className="text-xl font-normal tracking-tight m-0 p-0">
                        { __( 'Liquid Web Software', '%TEXTDOMAIN%' ) }
                    </h1>
                    <p className="text-sm text-muted-foreground leading-tight m-0 p-0">
                        { __( 'Manage your product licenses and features', '%TEXTDOMAIN%' ) }
                    </p>
                </div>
            </div>

            { isLoading ? (
                <div className="flex items-center justify-center gap-2 py-16 text-sm text-muted-foreground">
                    <Loader2 className="w-5 h-5 animate-spin" />
                    { __( 'Loading…', '%TEXTDOMAIN%' ) }
                </div>
            ) : (
                <ErrorBoundary>
                    <div className="flex flex-col gap-4">
                        <LegacyLicenseBanner />
                        { PRODUCTS.map( ( product ) => (
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
