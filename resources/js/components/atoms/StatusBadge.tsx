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

/**
 * @since 3.0.0
 */
export function StatusBadge( { status, requiredTier }: StatusBadgeProps ) {
    if ( status === 'installing' ) {
        return (
			<div className="flex flex-col items-end gap-0.5 w-36">
				<div className="flex items-center gap-2 w-full">
					<Download className="w-3.5 h-3.5 text-muted-foreground animate-pulse shrink-0" />
					<ProgressBar className="h-1.5 rounded-full bg-muted [&>div]:bg-primary" />
				</div>
				<span className="text-[10px] text-muted-foreground">{__( 'Installing…', '%TEXTDOMAIN%' )}</span>
			</div>
        );
    }

    if ( status === 'updating' ) {
        return (
			<div className="flex flex-col items-end gap-0.5 w-36">
				<div className="flex items-center gap-2 w-full">
					<Download className="w-3.5 h-3.5 text-muted-foreground animate-pulse shrink-0" />
					<ProgressBar className="h-1.5 rounded-full bg-muted [&>div]:bg-primary" />
				</div>
				<span className="text-[10px] text-muted-foreground">{ __( 'Updating…', '%TEXTDOMAIN%' ) }</span>
			</div>
        );
    }

	if ( status === 'enabling' ) {
        return (
			<span className='text-xs w-25 text-right text-primary flex items-center gap-1 justify-end' >
                <Loader2 className="w-3 h-3 animate-spin" />
                { __( 'Activating…', '%TEXTDOMAIN%' ) }
            </span>
        );
    }

    if ( status === 'disabling' ) {
        return (
			<span className='text-xs w-25 text-right text-muted-foreground flex items-center gap-1 justify-end' >
                <Loader2 className="w-3 h-3 animate-spin" />
                { __( 'Deactivating…', '%TEXTDOMAIN%' ) }
            </span>
        );
    }

    if ( status === 'enabled' ) {
        return (
			<span className='text-xs w-25 text-right text-green-600' >
				{__( 'Activated', '%TEXTDOMAIN%' )}
			</span>
        );
    }

    if ( status === 'available' ) {
        return (
			<span className='text-xs w-25 text-right text-muted-foreground' >
				{__( 'Deactivated', '%TEXTDOMAIN%' )}
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
