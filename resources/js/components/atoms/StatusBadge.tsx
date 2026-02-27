import { __, sprintf } from '@wordpress/i18n';
import { Check, Lock, Loader2 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';

export type FeatureStatus =
    | 'enabled'
    | 'available'
    | 'locked'
    | 'not-licensed'
    | 'enabling'
    | 'disabling';

interface StatusBadgeProps {
    status: FeatureStatus;
    requiredTier?: string;
}

/**
 * @since 3.0.0
 */
export function StatusBadge( { status, requiredTier }: StatusBadgeProps ) {
    if ( status === 'enabling' ) {
        return (
            <Badge variant="secondary">
                <Loader2 className="w-3 h-3 animate-spin" />
                { __( 'Enabling…', '%TEXTDOMAIN%' ) }
            </Badge>
        );
    }

    if ( status === 'disabling' ) {
        return (
            <Badge variant="secondary">
                <Loader2 className="w-3 h-3 animate-spin" />
                { __( 'Disabling…', '%TEXTDOMAIN%' ) }
            </Badge>
        );
    }

    if ( status === 'enabled' ) {
        return (
            <Badge variant="success">
                <Check className="w-3 h-3" />
                { __( 'Enabled', '%TEXTDOMAIN%' ) }
            </Badge>
        );
    }

    if ( status === 'available' ) {
        return (
            <Badge variant="secondary">
                { __( 'Disabled', '%TEXTDOMAIN%' ) }
            </Badge>
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
