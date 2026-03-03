/**
 * A single feature row in the product feature list.
 *
 * Div-based (not <tr>) — product sections use a divide-y list.
 * Feature data comes from the stellarwp/uplink store (REST API).
 * License/product state still comes from the Zustand store until Part 2.
 *
 * @see .plans/wp-data-store-features.md
 * @package StellarWP\Uplink
 */
import { useState } from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { useDispatch } from '@wordpress/data';
import { cn } from '@/lib/utils';
import { FeatureInfo } from '@/components/molecules/FeatureInfo';
import { StatusBadge } from '@/components/atoms/StatusBadge';
import { PurchaseLink } from '@/components/atoms/PurchaseLink';
import { Switch } from '@/components/ui/switch';
import { useLicenseStore, tierGte } from '@/stores/license-store';
import { useToastStore } from '@/stores/toast-store';
import { store as uplinkStore } from '@/store';
import type { Feature, Product } from '@/types/api';

interface FeatureRowProps {
    feature: Feature;
    product: Product;
}

/**
 * @since 3.0.0
 */
export function FeatureRow( { feature, product }: FeatureRowProps ) {
    const { getTierForProduct, productEnabled } = useLicenseStore();
    const { addToast } = useToastStore();
    const { setFeatureEnabled } = useDispatch( uplinkStore );

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

    const isAccessible = tierGte( activeTier, feature.tier );

    if ( ! isAccessible ) {
        const requiredTierObj = product.tiers.find( ( t ) => t.slug === feature.tier );
        const tierName = requiredTierObj?.name ?? feature.tier;
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

    const featureEnabled = feature.enabled;
    const [ isPending, setIsPending ] = useState( false );

    const handleToggle = ( checked: boolean ) => {
        setIsPending( true );
        setFeatureEnabled( feature.slug, checked );
        const msg = checked
            ? /* translators: %s is the name of the feature being enabled */
              sprintf( __( '%s enabled', '%TEXTDOMAIN%' ), feature.name )
            : /* translators: %s is the name of the feature being disabled */
              sprintf( __( '%s disabled', '%TEXTDOMAIN%' ), feature.name );
        addToast( msg, checked ? 'success' : 'default' );
        setIsPending( false );
    };

    const badgeStatus = featureEnabled ? 'enabled' : 'available';

    return (
        <div
            className={ cn(
                'flex items-center justify-between px-4 py-3 transition-colors',
                isPending ? 'opacity-75' : 'hover:bg-slate-50'
            ) }
        >
            <FeatureInfo
                name={ feature.name }
                description={ feature.description }
                isLocked={ false }
            />
            <div className="flex items-center gap-3 shrink-0 ml-4">
                <StatusBadge status={ badgeStatus } />
                <Switch
                    checked={ featureEnabled }
                    onCheckedChange={ handleToggle }
                    disabled={ isPending }
                    aria-label={
                        featureEnabled
                            ? /* translators: %s is the name of the feature to disable */
                              sprintf( __( 'Disable %s', '%TEXTDOMAIN%' ), feature.name )
                            : /* translators: %s is the name of the feature to enable */
                              sprintf( __( 'Enable %s', '%TEXTDOMAIN%' ), feature.name )
                    }
                />
            </div>
        </div>
    );
}
