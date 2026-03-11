/**
 * Product section: sticky dark header + feature list + tier group accordions.
 *
 * Available features render as FeatureRow entries. Locked features are
 * grouped by tier and rendered inside collapsible TierGroup accordions.
 *
 * @package StellarWP\\Uplink
 */
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { Badge } from '@/components/ui/badge';
import { ProductLogo } from '@/components/atoms/ProductLogo';
import { FeatureRow } from '@/components/molecules/FeatureRow';
import { TierGroup } from '@/components/molecules/TierGroup';
import { store as uplinkStore } from '@/store';
import type { Product } from '@/types/api';

interface ProductSectionProps {
    product: Product;
}

/**
 * @since 3.0.0
 */
export function ProductSection( { product }: ProductSectionProps ) {
    const { features, hasLicense, licenseProduct, catalogTiers } = useSelect(
        ( select ) => {
            const allFeatures = select( uplinkStore ).getFeaturesByGroup( product.slug );
            const licenseProducts = select( uplinkStore ).getLicenseProducts();
            const catalog = select( uplinkStore ).getProductCatalog( product.slug );
            return {
                features: allFeatures,
                hasLicense: select( uplinkStore ).hasLicense(),
                licenseProduct: licenseProducts.find( ( lp ) => lp.product_slug === product.slug ) ?? null,
                catalogTiers: catalog?.tiers ?? [],
            };
        },
        [ product.slug ],
    );

    const availableFeatures = features.filter( ( f ) => f.is_available );
    const lockedFeatures = features.filter( ( f ) => ! f.is_available );

    const activeCount = availableFeatures.filter( ( f ) => f.is_enabled ).length;
    const deactivatedCount = availableFeatures.filter( ( f ) => ! f.is_enabled ).length;

    const tierName = licenseProduct
        ? ( catalogTiers.find( ( t ) => t.slug === licenseProduct.tier )?.name ?? licenseProduct.tier )
        : null;

    // Group locked features by their minimum tier slug.
    const lockedByTier = product.tiers.reduce<Record<string, typeof lockedFeatures>>(
        ( acc, tier ) => {
            acc[ tier.slug ] = lockedFeatures.filter( ( f ) => f.tier === tier.slug );
            return acc;
        },
        {}
    );

    const hasContent = availableFeatures.length > 0 || lockedFeatures.length > 0;

    return (
        <section id={ product.slug } className="scroll-mt-20">
            {/* Sticky dark product header — clears WP admin bar */}
            <div className="sticky top-[var(--wp-admin--admin-bar--height,32px)] z-10
                            flex items-center gap-3 px-4 py-3
                            bg-neutral-800 text-white">
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

            { hasLicense && hasContent && (
                <div className="border border-t-0 rounded-b-lg overflow-hidden">
                    { availableFeatures.map( ( feature ) => (
                        <FeatureRow
                            key={ feature.slug }
                            feature={ feature }
                            product={ product }
                        />
                    ) ) }

                    { product.tiers.map( ( tier ) => {
                        const locked = lockedByTier[ tier.slug ] ?? [];
                        if ( locked.length === 0 ) return null;
                        return (
                            <TierGroup
                                key={ tier.slug }
                                tier={ tier }
                                features={ locked }
                                product={ product }
                            />
                        );
                    } ) }
                </div>
            ) }
        </section>
    );
}
