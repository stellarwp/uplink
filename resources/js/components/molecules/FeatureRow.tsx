/**
 * A single feature row in the product feature list.
 *
 * Div-based (not <tr>) — product sections use a divide-y list.
 * Connects to the license store and toast store.
 *
 * @package StellarWP\Uplink
 */
import { __, sprintf } from '@wordpress/i18n';
import { cn } from '@/lib/utils';
import { FeatureInfo } from '@/components/molecules/FeatureInfo';
import { StatusBadge } from '@/components/atoms/StatusBadge';
import { PurchaseLink } from '@/components/atoms/PurchaseLink';
import { Switch } from '@/components/ui/switch';
import { useLicenseStore, tierGte } from '@/stores/license-store';
import { useToastStore } from '@/stores/toast-store';
import type { ProductFeature, Product } from '@/types/api';

interface FeatureRowProps {
    feature: ProductFeature;
    product: Product;
}

/**
 * @since TBD
 */
export function FeatureRow( { feature, product }: FeatureRowProps ) {
    const { getTierForProduct, isFeatureEnabled, toggleFeature, productEnabled } =
        useLicenseStore();
    const { addToast } = useToastStore();

    const activeTier = getTierForProduct( product.slug );
    const isProductOn = productEnabled[ product.slug ] ?? false;

    // Product manually disabled by the user — hide all its features.
    if ( activeTier !== null && ! isProductOn ) {
        return null;
    }

    // No license for this product — show every feature as not-licensed.
    if ( activeTier === null ) {
        const starterTier = product.tiers.find( ( t ) => t.slug === 'starter' ) ?? product.tiers[ 0 ];

        return (
            <div className="flex items-center justify-between px-4 py-3 bg-slate-50/50">
                <FeatureInfo
                    name={ feature.name }
                    description={ feature.description }
                    isLocked={ true }
                />
                <div className="flex items-center gap-3 shrink-0 ml-4">
                    <StatusBadge status="not-licensed" />
                    <PurchaseLink
                        tierName={ starterTier.name }
                        upgradeUrl={ starterTier.upgradeUrl }
                        mode="learn-more"
                    />
                </div>
            </div>
        );
    }

    const isAccessible = tierGte( activeTier, feature.requiredTier );

    if ( ! isAccessible ) {
        const requiredTierObj = product.tiers.find( ( t ) => t.slug === feature.requiredTier );
        const tierName = requiredTierObj?.name ?? feature.requiredTier;
        const upgradeUrl = requiredTierObj?.upgradeUrl ?? '#';

        return (
            <div className="flex items-center justify-between px-4 py-3 bg-slate-50/50">
                <FeatureInfo
                    name={ feature.name }
                    description={ feature.description }
                    isLocked={ true }
                />
                <div className="flex items-center gap-3 shrink-0 ml-4">
                    <StatusBadge status="locked" requiredTier={ tierName } />
                    <PurchaseLink tierName={ tierName } upgradeUrl={ upgradeUrl } />
                </div>
            </div>
        );
    }

    const featureEnabled = isFeatureEnabled( feature.id, product.slug );

    const handleToggle = ( checked: boolean ) => {
        toggleFeature( feature.id, product.slug, checked );
        const msg = checked
            ? sprintf( __( '%s enabled', '%TEXTDOMAIN%' ), feature.name )
            : sprintf( __( '%s disabled', '%TEXTDOMAIN%' ), feature.name );
        addToast( msg, checked ? 'success' : 'default' );
    };

    return (
        <div
            className={ cn(
                'flex items-center justify-between px-4 py-3 transition-colors',
                'hover:bg-slate-50'
            ) }
        >
            <FeatureInfo
                name={ feature.name }
                description={ feature.description }
                isLocked={ false }
            />
            <div className="flex items-center gap-3 shrink-0 ml-4">
                <StatusBadge status={ featureEnabled ? 'enabled' : 'available' } />
                <Switch
                    checked={ featureEnabled }
                    onCheckedChange={ handleToggle }
                    aria-label={
                        featureEnabled
                            ? sprintf( __( 'Disable %s', '%TEXTDOMAIN%' ), feature.name )
                            : sprintf( __( 'Enable %s', '%TEXTDOMAIN%' ), feature.name )
                    }
                />
            </div>
        </div>
    );
}
