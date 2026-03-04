/**
 * Product section: sticky header + feature list.
 *
 * Renders for both licensed and unlicensed products.
 * License state and feature availability come from the stellarwp/uplink store.
 *
 * @package StellarWP\\Uplink
 */
import { __ } from '@wordpress/i18n';
import { Loader2 } from 'lucide-react';
import { useSelect } from '@wordpress/data';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { BrandIcon } from '@/components/atoms/BrandIcon';
import { FeatureRow } from '@/components/molecules/FeatureRow';
import { BRAND_CONFIGS } from '@/data/brands';
import { store as uplinkStore } from '@/store';
import type { Product } from '@/types/api';

interface ProductSectionProps {
    product: Product;
    /** Opens the Add License dialog — passed down from MyProductsTab */
    onAddLicense: () => void;
}

/**
 * @since 3.0.0
 */
export function ProductSection( { product, onAddLicense }: ProductSectionProps ) {
    const config = BRAND_CONFIGS[ product.slug ];

    // Calling getLicense() triggers the getLicense resolver.
    const hasLicense = useSelect(
        ( select ) => {
            select( uplinkStore ).getLicense();
            return select( uplinkStore ).hasLicense();
        },
        [],
    );

    // Features come from the REST API via the store resolver.
    // Calling getFeatures() inside useSelect triggers the resolver;
    // getFeaturesByGroup then filters the cached result for this product.
    const features = useSelect(
        ( select ) => {
            select( uplinkStore ).getFeatures();
            return select( uplinkStore ).getFeaturesByGroup( product.slug );
        },
        [ product.slug ],
    );

    // True while the getFeatures resolver has not yet completed.
    const isLoadingFeatures = useSelect(
        ( select ) => {
            const s = select( uplinkStore ) as unknown as {
                hasFinishedResolution: ( name: string, args?: unknown[] ) => boolean;
            };
            return ! s.hasFinishedResolution( 'getFeatures', [] );
        },
        [],
    );

    return (
        <div className="rounded-lg border border-border bg-background overflow-clip">
            {/* Sticky product header — clears WP admin bar */}
            <div className="sticky top-[var(--wp-admin--admin-bar--height,0px)] z-10 flex items-center justify-between px-4 py-3 bg-background border-b border-border">
                <div className="flex items-center gap-3">
                    { config && (
                        <BrandIcon icon={ config.icon } colorClass={ config.colorClass } />
                    ) }
                    <div>
                        <div className="flex items-center gap-2 flex-wrap">
                            <h3 className="text-base font-semibold text-foreground m-0">
                                { product.name }
                            </h3>
                            { hasLicense ? (
                                <Badge variant="success">
                                    { __( 'Active license', '%TEXTDOMAIN%' ) }
                                </Badge>
                            ) : (
                                <Badge variant="outline">
                                    { __( 'No license', '%TEXTDOMAIN%' ) }
                                </Badge>
                            ) }
                        </div>
                        <p className="text-xs text-muted-foreground m-0">
                            { product.tagline }
                        </p>
                    </div>
                </div>

                { ! hasLicense && (
                    <Button size="sm" onClick={ onAddLicense }>
                        { __( 'Add License', '%TEXTDOMAIN%' ) }
                    </Button>
                ) }
            </div>

            {/* Feature list — visible when licensed */}
            { hasLicense && (
                <div className="divide-y divide-border">
                    { isLoadingFeatures ? (
                        <div className="flex items-center justify-center gap-2 px-4 py-6 text-sm text-muted-foreground">
                            <Loader2 className="w-4 h-4 animate-spin" />
                            { __( 'Loading features…', '%TEXTDOMAIN%' ) }
                        </div>
                    ) : (
                        features.map( ( feature ) => (
                            <FeatureRow
                                key={ feature.slug }
                                feature={ feature }
                                product={ product }
                            />
                        ) )
                    ) }
                </div>
            ) }

            { ! hasLicense && (
                <p className="px-4 py-6 text-sm text-muted-foreground text-center">
                    { __( 'Add a license to unlock features.', '%TEXTDOMAIN%' ) }
                </p>
            ) }
        </div>
    );
}
