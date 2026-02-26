import { __, sprintf } from '@wordpress/i18n';
import { ExternalLink } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface PurchaseLinkProps {
    /** Tier name to upgrade to (e.g. "Agency") */
    tierName: string;
    /** Upgrade destination URL */
    upgradeUrl: string;
    /** "upgrade" shows "Upgrade to Tier", "learn-more" shows "Learn more" */
    mode?: 'upgrade' | 'learn-more';
}

/**
 * @since TBD
 */
export function PurchaseLink( { tierName, upgradeUrl, mode = 'upgrade' }: PurchaseLinkProps ) {
    const label =
        mode === 'upgrade'
            ? /* translators: %s is the name of the license tier to upgrade to */
              sprintf( __( 'Upgrade to %s', '%TEXTDOMAIN%' ), tierName )
            : __( 'Learn more', '%TEXTDOMAIN%' );

    return (
        <Button
            variant="outline"
            size="xs"
            asChild
        >
            <a
                href={ upgradeUrl }
                target="_blank"
                rel="noopener noreferrer"
                aria-label={ label }
            >
                { label }
                <ExternalLink className="w-3 h-3" />
            </a>
        </Button>
    );
}
