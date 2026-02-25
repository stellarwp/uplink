import { __, sprintf } from '@wordpress/i18n';

interface UpsellActionProps {
    featureName: string;
    upgradeUrl: string;
}

/**
 * @since TBD
 */
export function UpsellAction( { featureName, upgradeUrl }: UpsellActionProps ) {
    return (
        <a
            href={ upgradeUrl }
            target="_blank"
            rel="noopener noreferrer"
            className="bg-primary/10 hover:bg-primary/20 text-primary px-3 py-1.5 rounded text-xs font-semibold transition-colors"
            aria-label={ sprintf( __( 'Buy license for %s', '%TEXTDOMAIN%' ), featureName ) }
        >
            { __( 'Buy Now', '%TEXTDOMAIN%' ) }
        </a>
    );
}
