/**
 * Product section: sticky dark header + feature list + tier group accordions.
 *
 * Available features render as FeatureRow entries. Locked features are
 * grouped by tier and rendered inside collapsible TierGroup accordions.
 *
 * Header counts (active / deactivated) always reflect the full unfiltered
 * feature set so they remain stable while the user searches.
 *
 * @package StellarWP\Uplink
 */
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { Badge } from '@/components/ui/badge';
import { ProductLogo } from '@/components/atoms/ProductLogo';
import { FeatureRow } from '@/components/molecules/FeatureRow';
import { TierGroup } from '@/components/molecules/TierGroup';
import { store as uplinkStore } from '@/store';
import { useFilter } from '@/context/filter-context';
import { useProductFeatureGroups } from '@/hooks/useProductFeatureGroups';
import type { Product } from '@/types/api';

interface ProductSectionProps {
    product: Product;
}

/**
 * @since 3.0.0
 */
export function ProductSection( { product }: ProductSectionProps ) {
    const { searchQuery } = useFilter();
    const isSearching = searchQuery.trim().length > 0;

    // Full unfiltered set — used only for header counts so they stay stable.
    const { hasLicense, licenseProduct, allFeaturesUnfiltered } = useSelect(
        ( select ) => {
            const licenseProducts = select( uplinkStore ).getLicenseProducts();
            return {
                allFeaturesUnfiltered: select( uplinkStore ).getFeaturesByProduct( product.slug ),
                hasLicense:            select( uplinkStore ).hasLicense(),
                licenseProduct:        licenseProducts.find( ( lp ) => lp.product_slug === product.slug ) ?? null,
            };
        },
        [ product.slug ],
    );

    const { availableFeatures, lockedByTier, sortedCatalogTiers } = useProductFeatureGroups( product.slug );

    // Counts derived from the unfiltered set — unaffected by search.
    const activeCount      = allFeaturesUnfiltered.filter( ( f ) => f.is_available && f.is_enabled ).length;
    const deactivatedCount = allFeaturesUnfiltered.filter( ( f ) => f.is_available && ! f.is_enabled ).length;

    const tierName = licenseProduct
        ? ( sortedCatalogTiers.find( ( t ) => t.slug === licenseProduct.tier )?.name ?? licenseProduct.tier )
        : null;

    const hasContent = availableFeatures.length > 0 || Object.values( lockedByTier ).some( ( f ) => f.length > 0 );

    return (
        <section id={ product.slug } className="scroll-mt-20">
			<div className="h-0"></div>
            <div className="flex items-center gap-3 px-4 py-3 bg-neutral-800 text-white sticky top-0 z-10 border-x border-neutral-800 transition-[border-radius] rounded-t-lg border-t">
                <ProductLogo slug={ product.slug } size={ 28 } />
                <h2 className="text-base font-semibold m-0 p-0 text-white">
                    { product.name }
                </h2>
                { tierName ? (
                    <Badge variant="gradient">{ tierName }</Badge>
                ) : (
                    <Badge variant="outline" className="text-white border-white/40">
                        { __( 'Not Licensed', '%TEXTDOMAIN%' ) }
                    </Badge>
                ) }
                <span className="ml-auto text-xs text-white/70">
                    { activeCount } { __( 'active', '%TEXTDOMAIN%' ) }
                    { ' · ' }
                    { deactivatedCount } { __( 'deactivated', '%TEXTDOMAIN%' ) }
                </span>
            </div>

            { ! hasLicense && (
                <div className="border border-t-0 rounded-b-lg">
                    <p className="px-4 py-6 text-sm text-muted-foreground text-center">
                        { __( 'Add a license to unlock features.', '%TEXTDOMAIN%' ) }
                    </p>
                </div>
            ) }

            { hasLicense && isSearching && ! hasContent && (
                <div className="border border-t-0 rounded-b-lg">
                    <p className="px-4 py-6 text-sm text-muted-foreground text-center">
                        { __( 'No features match your search.', '%TEXTDOMAIN%' ) }
                    </p>
                </div>
            ) }

            { hasLicense && hasContent && (
                <div className="border border-t-0 rounded-b-lg overflow-hidden">
                    { availableFeatures.map( ( feature ) => (
                        <FeatureRow
                            key={ feature.slug }
                            feature={ feature }
                        />
                    ) ) }

                    { sortedCatalogTiers.map( ( tier ) => {
                        const locked = lockedByTier[ tier.slug ] ?? [];
                        if ( locked.length === 0 ) return null;
                        return (
                            <TierGroup
                                key={ tier.slug }
                                tier={ tier }
                                features={ locked }
                                forceOpen={ isSearching }
                            />
                        );
                    } ) }
                </div>
            ) }
        </section>
    );
}
