import { __ } from '@wordpress/i18n';
import { Check, Lock } from 'lucide-react';
import { Badge } from '@/components/ui/badge';

export type FeatureStatus = 'enabled' | 'available' | 'locked' | 'not-licensed';

interface StatusBadgeProps {
    status: FeatureStatus;
    requiredTier?: string;
}

/**
 * @since TBD
 */
export function StatusBadge( { status, requiredTier }: StatusBadgeProps ) {
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
                { `Requires ${ requiredTier }` }
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
