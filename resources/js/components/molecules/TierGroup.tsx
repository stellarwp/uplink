/**
 * Collapsible accordion grouping locked features under a tier header.
 *
 * Shows the tier name, feature count, a lock indicator, and an upgrade
 * button. Expanding the accordion reveals the locked FeatureRow entries.
 *
 * @package StellarWP\Uplink
 */
import { useState } from 'react';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { ChevronRight, ChevronDown, Lock, ExternalLink } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { FeatureRow } from '@/components/molecules/FeatureRow';
import { store as uplinkStore } from '@/store';
import type { Feature, Product, Tier } from '@/types/api';

interface TierGroupProps {
    tier:       Tier;
    features:   Feature[];
    product:    Product;
    forceOpen?: boolean;
}

/**
 * @since 3.0.0
 */
export function TierGroup( { tier, features, product, forceOpen = false }: TierGroupProps ) {
    const [ expanded, setExpanded ] = useState( false );
    const isOpen = expanded || forceOpen;
    const Chevron = isOpen ? ChevronDown : ChevronRight;

    // Prefer the API's purchase_url; fall back to the static fixture upgradeUrl.
    const catalogTier = useSelect(
        ( select ) => select( uplinkStore ).getCatalogTier( product.slug, tier.slug ),
        [ product.slug, tier.slug ]
    );
    const upgradeUrl = catalogTier?.purchase_url ?? tier.upgradeUrl;

    return (
        <>
            <button
                type="button"
                onClick={ () => setExpanded( ! expanded ) }
                className="w-full flex items-center gap-2 px-4 py-3 text-left
                           bg-muted/50 hover:bg-muted/70 transition-colors border-b"
            >
                <Chevron className="w-4 h-4 shrink-0 text-muted-foreground" />
                <span className="font-medium text-sm">
                    { tier.name } { __( 'Features', '%TEXTDOMAIN%' ) }
                </span>
                <Badge variant="secondary" className="text-xs">
                    { features.length }
                </Badge>
                <Lock className="w-3.5 h-3.5 text-muted-foreground ml-1" />
                <div className="flex-1" />
                <Button
                    variant="outline"
                    size="sm"
                    className="gap-1 text-xs h-7"
                    onClick={ ( e ) => {
                        e.stopPropagation();
                        window.open( upgradeUrl, '_blank', 'noopener,noreferrer' );
                    } }
                >
                    <ExternalLink className="w-3 h-3" />
                    { __( 'Upgrade to', '%TEXTDOMAIN%' ) }{ ' ' }{ tier.name }
                </Button>
            </button>

            { isOpen && features.map( ( feature ) => (
                <FeatureRow
                    key={ feature.slug }
                    feature={ feature }
                    product={ product }
                />
            ) ) }
        </>
    );
}
