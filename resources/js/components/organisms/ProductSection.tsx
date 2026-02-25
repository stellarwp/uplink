/**
 * Product section: sticky header + feature list.
 *
 * @package StellarWP\Uplink
 */
import { __, sprintf } from '@wordpress/i18n';
import { Badge } from '@/components/ui/badge';
import { Switch } from '@/components/ui/switch';
import { BrandIcon } from '@/components/atoms/BrandIcon';
import { FeatureRow } from '@/components/molecules/FeatureRow';
import { BRAND_CONFIGS } from '@/data/brands';
import { useLicenseStore } from '@/stores/license-store';
import { useToastStore } from '@/stores/toast-store';
import type { Product } from '@/types/api';

interface ProductSectionProps {
    product: Product;
}

/**
 * @since TBD
 */
export function ProductSection( { product }: ProductSectionProps ) {
    const { getLicenseForProduct, getTierForProduct, productEnabled, toggleProduct } =
        useLicenseStore();
    const { addToast } = useToastStore();

    const license = getLicenseForProduct( product.slug );
    const tier = getTierForProduct( product.slug );
    const isEnabled = productEnabled[ product.slug ] ?? false;
    const config = BRAND_CONFIGS[ product.slug ];

    if ( ! license ) return null;

    const tierName =
        product.tiers.find( ( t ) => t.slug === tier )?.name ?? tier ?? '';

    const licenseTypeLabel =
        license.type === 'legacy'
            ? __( 'Legacy', '%TEXTDOMAIN%' )
            : __( 'Active license', '%TEXTDOMAIN%' );

    const handleProductToggle = ( checked: boolean ) => {
        toggleProduct( product.slug, checked );
        const msg = checked
            ? sprintf( __( '%s enabled', '%TEXTDOMAIN%' ), product.name )
            : sprintf( __( '%s disabled', '%TEXTDOMAIN%' ), product.name );
        addToast( msg, checked ? 'success' : 'default' );
    };

    return (
        <div className="rounded-lg border border-border bg-background overflow-hidden">
            {/* Sticky product header */}
            <div className="sticky top-0 z-10 flex items-center justify-between px-4 py-3 bg-background border-b border-border">
                <div className="flex items-center gap-3">
                    { config && (
                        <BrandIcon icon={ config.icon } colorClass={ config.colorClass } />
                    ) }
                    <div>
                        <div className="flex items-center gap-2 flex-wrap">
                            <h3 className="text-base font-semibold text-foreground m-0">
                                { product.name }
                            </h3>
                            { tierName && (
                                <Badge variant="info">{ tierName }</Badge>
                            ) }
                            <Badge variant={ license.type === 'legacy' ? 'warning' : 'success' }>
                                { licenseTypeLabel }
                            </Badge>
                        </div>
                        <p className="text-xs text-muted-foreground m-0">
                            { product.tagline }
                        </p>
                    </div>
                </div>

                <Switch
                    checked={ isEnabled }
                    onCheckedChange={ handleProductToggle }
                    aria-label={
                        isEnabled
                            ? sprintf( __( 'Disable %s', '%TEXTDOMAIN%' ), product.name )
                            : sprintf( __( 'Enable %s', '%TEXTDOMAIN%' ), product.name )
                    }
                />
            </div>

            {/* Feature list */}
            { isEnabled && (
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

            { ! isEnabled && (
                <p className="px-4 py-6 text-sm text-muted-foreground text-center">
                    { sprintf(
                        __( '%s is disabled. Enable it to manage features.', '%TEXTDOMAIN%' ),
                        product.name
                    ) }
                </p>
            ) }
        </div>
    );
}
