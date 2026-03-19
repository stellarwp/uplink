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
import { ChevronRight, ChevronDown, Lock, ExternalLink } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { FeatureRow } from '@/components/molecules/FeatureRow';
import type { CatalogTier, Feature } from '@/types/api';

interface TierGroupProps {
    tier:       CatalogTier;
    features:   Feature[];
    forceOpen?: boolean;
}

/**
 * @since 3.0.0
 */
export function TierGroup( { tier, features, forceOpen = false }: TierGroupProps ) {
    const [ expanded, setExpanded ] = useState( false );
    const isOpen = expanded || forceOpen;
    const Chevron = isOpen ? ChevronDown : ChevronRight;

    return (
        <>
            <div className="w-full flex items-center gap-2 px-4 py-3 bg-muted/50 border-b">
                <div
                    onClick={ () => setExpanded( ! expanded ) }
                    className="flex items-center gap-2 cursor-pointer hover:opacity-80 transition-opacity"
                >
                    <Chevron className="w-4 h-4 shrink-0 text-muted-foreground" />
                    <span className="font-medium text-sm">
                        { tier.name } { __( 'Features', '%TEXTDOMAIN%' ) }
                    </span>
                    <Badge variant="secondary" className="text-xs">
                        { features.length }
                    </Badge>
                    <Lock className="w-3.5 h-3.5 text-muted-foreground ml-1" />
                </div>
                <Button
                    variant="outline"
                    size="sm"
                    className="gap-1 text-xs h-7 ml-auto shrink-0"
                    onClick={ () => window.open( tier.purchase_url, '_blank', 'noopener,noreferrer' ) }
                >
                    <ExternalLink className="w-3 h-3" />
                    { __( 'Upgrade to', '%TEXTDOMAIN%' ) }{ ' ' }{ tier.name }
                </Button>
            </div>

            { isOpen && features.map( ( feature ) => (
                <FeatureRow
                    key={ feature.slug }
                    feature={ feature }
                    upgradeTierName={ tier.name }
                />
            ) ) }
        </>
    );
}
