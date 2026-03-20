/**
 * Amber warning banner shown when one or more legacy licenses are active.
 *
 * Legacy license data is fetched from the REST API via the store's
 * getLegacyLicenses resolver.
 *
 * @package StellarWP\Uplink
 */
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { AlertTriangle } from 'lucide-react';
import { store as uplinkStore } from '@/store';

/**
 * @since 3.0.0
 */
export function LegacyLicenseBanner() {
    const hasLegacy = useSelect(
        ( select ) => select( uplinkStore ).hasLegacyLicenses(),
        []
    );

    if ( ! hasLegacy ) return null;

    return (
        <div
            role="alert"
            className="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"
        >
            <AlertTriangle className="w-4 h-4 shrink-0 mt-0.5" />
            <p className="m-0">
                { __( 'You have one or more legacy licenses active. Legacy licenses work but do not receive automatic upgrades. Consider upgrading to a unified license for the latest features.', '%TEXTDOMAIN%' ) }
            </p>
        </div>
    );
}
