import { __, sprintf } from '@wordpress/i18n';
import { ProgressBar } from '@wordpress/components';
import { Lock, Loader2, Download } from 'lucide-react';
import { Badge } from '@/components/ui/badge';

export type FeatureStatus =
    | 'enabled'
    | 'available'
    | 'locked'
    | 'not-licensed'
    | 'installing'
    | 'enabling'
    | 'disabling'
    | 'updating';

interface StatusBadgeProps {
    status: FeatureStatus;
    requiredTier?: string;
}

const SPAN_CONFIG: Record< string, { label: string; color: string } > = {
    enabling:  { label: __( 'Activating\u2026',   '%TEXTDOMAIN%' ), color: 'text-primary' },
    disabling: { label: __( 'Deactivating\u2026', '%TEXTDOMAIN%' ), color: 'text-muted-foreground' },
    enabled:   { label: __( 'Activated',          '%TEXTDOMAIN%' ), color: 'text-green-600' },
    available: { label: __( 'Deactivated',        '%TEXTDOMAIN%' ), color: 'text-muted-foreground' },
};

/**
 * @since 3.0.0
 */
export function StatusBadge( { status, requiredTier }: StatusBadgeProps ) {
    if ( status === 'installing' || status === 'updating' ) {
        const label = status === 'installing'
            ? __( 'Installing\u2026', '%TEXTDOMAIN%' )
            : __( 'Updating\u2026',   '%TEXTDOMAIN%' );
        return (
            <div className="flex flex-col items-end gap-0.5 w-36">
                <div className="flex items-center gap-2 w-full">
                    <Download className="w-3.5 h-3.5 text-muted-foreground animate-pulse shrink-0" />
                    <ProgressBar className="h-1.5 rounded-full bg-muted [&>div]:bg-primary" />
                </div>
                <span className="text-[10px] text-muted-foreground">{ label }</span>
            </div>
        );
    }

    const config = SPAN_CONFIG[ status ];
    if ( config ) {
        const showSpinner = status === 'enabling' || status === 'disabling';
        return (
            <span className={ `text-xs w-25 text-right ${ config.color } flex items-center gap-1 justify-end` }>
                { showSpinner && <Loader2 className="w-3 h-3 animate-spin" /> }
                { config.label }
            </span>
        );
    }

    if ( status === 'locked' && requiredTier ) {
        return (
            <Badge variant="warning">
                <Lock className="w-3 h-3" />
                {/* translators: %s is the name of the required license tier */}
                { sprintf( __( 'Requires %s', '%TEXTDOMAIN%' ), requiredTier ) }
            </Badge>
        );
    }

    // not-licensed or locked without tier label
    return (
        <Badge variant="outline">
            <Lock className="w-3 h-3" />
            { __( 'Not Licensed', '%TEXTDOMAIN%' ) }
        </Badge>
    );
}
