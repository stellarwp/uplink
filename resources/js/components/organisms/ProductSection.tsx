/**
 * Product section: sticky header + feature list.
 *
 * Renders for both licensed and unlicensed products.
 * License state and feature availability come from the stellarwp/uplink store.
 *
 * @package StellarWP\\Uplink
 */
import { useState } from 'react';
import { __, sprintf } from '@wordpress/i18n';
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
    // TODO: product active/inactive state is local-only for now. When the
    // product-status REST endpoint lands (Phase 5), replace this with a
    // store action + selector so the state is persisted server-side.
    const [ productActive, setProductActive ] = useState( true );

    const { features, hasLicense } = useSelect(
        ( select ) => ({
            features: select( uplinkStore ).getFeaturesByGroup( product.slug ),
            hasLicense: select( uplinkStore ).hasLicense(),
        }),
        [product.slug],
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

                { hasLicense ? (
                    <Button
                        size="sm"
                        variant={ productActive ? 'outline' : 'default' }
                        onClick={ () => setProductActive( ( v ) => ! v ) }
                        aria-label={
                            productActive
                                ? /* translators: %s is the product name */
                                  sprintf( __( 'Deactivate %s', '%TEXTDOMAIN%' ), product.name )
                                : /* translators: %s is the product name */
                                  sprintf( __( 'Activate %s', '%TEXTDOMAIN%' ), product.name )
                        }
                    >
                        { productActive
                            ? /* translators: %s is the product name */
                              sprintf( __( 'Deactivate %s', '%TEXTDOMAIN%' ), product.name )
                            : /* translators: %s is the product name */
                              sprintf( __( 'Activate %s', '%TEXTDOMAIN%' ), product.name ) }
                    </Button>
                ) : (
                    <Button size="sm" onClick={ onAddLicense }>
                        { __( 'Add License', '%TEXTDOMAIN%' ) }
                    </Button>
                ) }
            </div>

            {/* Feature list — visible when licensed and product is active */}
            { hasLicense && productActive && (
                <div className="divide-y divide-border">
                    { features.map( ( feature ) => (
                        <FeatureRow
                            key={ feature.slug }
                            feature={ feature }
                            product={ product }
                        />
                    ) ) }
                </div>
            ) }

            { ! hasLicense && (
                <p className="px-4 py-6 text-sm text-muted-foreground text-center">
                    { __( 'Add a license to unlock features.', '%TEXTDOMAIN%' ) }
                </p>
            ) }

            { hasLicense && ! productActive && (
                <p className="px-4 py-6 text-sm text-muted-foreground text-center">
                    { /* translators: %s is the product name */
                      sprintf( __( '%s is deactivated. Activate it to manage features.', '%TEXTDOMAIN%' ), product.name ) }
                </p>
            ) }
        </div>
    );
}
