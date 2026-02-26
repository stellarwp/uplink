/**
 * Product section: sticky header + feature list.
 *
 * Renders for both licensed and unlicensed products.
 * When no license is active, features show in a not-licensed state.
 *
 * @package StellarWP\Uplink
 */
import { useState } from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { Loader2 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { BrandIcon } from '@/components/atoms/BrandIcon';
import { FeatureRow } from '@/components/molecules/FeatureRow';
import { BRAND_CONFIGS } from '@/data/brands';
import { useLicenseStore } from '@/stores/license-store';
import { useToastStore } from '@/stores/toast-store';
import type { Product } from '@/types/api';

interface ProductSectionProps {
    product: Product;
    /** Opens the Add License dialog — passed down from MyProductsTab */
    onAddLicense: () => void;
}

/**
 * @since TBD
 */
export function ProductSection( { product, onAddLicense }: ProductSectionProps ) {
    const { getLicenseForProduct, getTierForProduct, productEnabled, toggleProduct } =
        useLicenseStore();
    const { addToast } = useToastStore();

    const license = getLicenseForProduct( product.slug );
    const tier = getTierForProduct( product.slug );
    const isEnabled = productEnabled[ product.slug ] ?? false;
    const config = BRAND_CONFIGS[ product.slug ];
    const [ isPending, setIsPending ] = useState( false );

    const tierName =
        product.tiers.find( ( t ) => t.slug === tier )?.name ?? '';

    const handleProductToggle = async () => {
        const next = ! isEnabled;
        setIsPending( true );
        await toggleProduct( product.slug, next );
        /* translators: %s is the name of the product being activated */
        const msg = next
            ? sprintf( __( '%s activated', '%TEXTDOMAIN%' ), product.name )
            : /* translators: %s is the name of the product being deactivated */
              sprintf( __( '%s deactivated', '%TEXTDOMAIN%' ), product.name );
        addToast( msg, next ? 'success' : 'default' );
        setIsPending( false );
    };

    // Features are visible only when the product is licensed and enabled.
    const showFeatures = !! license && isEnabled;

    // During the pending phase the store has already applied the optimistic update,
    // so isEnabled reflects the *new* value. Use that to pick the right label.
    const buttonLabel = isPending
        ? ( isEnabled
            ? /* translators: %s is the name of the product being activated */
              sprintf( __( 'Activating %s…', '%TEXTDOMAIN%' ), product.name )
            : /* translators: %s is the name of the product being deactivated */
              sprintf( __( 'Deactivating %s…', '%TEXTDOMAIN%' ), product.name ) )
        : ( isEnabled
            ? /* translators: %s is the name of the product to deactivate */
              sprintf( __( 'Deactivate %s', '%TEXTDOMAIN%' ), product.name )
            : /* translators: %s is the name of the product to activate */
              sprintf( __( 'Activate %s', '%TEXTDOMAIN%' ), product.name ) );

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
                            { license ? (
                                <>
                                    { tierName && (
                                        <Badge variant="info">{ tierName }</Badge>
                                    ) }
                                    <Badge variant={ license.type === 'legacy' ? 'warning' : 'success' }>
                                        { license.type === 'legacy'
                                            ? __( 'Legacy', '%TEXTDOMAIN%' )
                                            : __( 'Active license', '%TEXTDOMAIN%' ) }
                                    </Badge>
                                </>
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

                { license ? (
                    <Button
                        size="sm"
                        variant={ isEnabled ? 'outline' : 'default' }
                        onClick={ handleProductToggle }
                        disabled={ isPending }
                    >
                        { isPending && <Loader2 className="w-3 h-3 animate-spin" /> }
                        { buttonLabel }
                    </Button>
                ) : (
                    <Button size="sm" onClick={ onAddLicense }>
                        { __( 'Add License', '%TEXTDOMAIN%' ) }
                    </Button>
                ) }
            </div>

            {/* Feature list */}
            { showFeatures && (
                <div className="divide-y divide-border">
                    { product.features.map( ( feature ) => (
                        <FeatureRow
                            key={ feature.id }
                            feature={ feature }
                            product={ product }
                        />
                    ) ) }
                </div>
            ) }

            { ! showFeatures && (
                <p className="px-4 py-6 text-sm text-muted-foreground text-center">
                    { ! license
                        ? /* translators: %s is the name of the product */
                          sprintf( __( 'Add a license to unlock %s features.', '%TEXTDOMAIN%' ), product.name )
                        : /* translators: %s is the name of the product that is deactivated */
                          sprintf( __( '%s is deactivated. Activate it to manage features.', '%TEXTDOMAIN%' ), product.name )
                    }
                </p>
            ) }
        </div>
    );
}
