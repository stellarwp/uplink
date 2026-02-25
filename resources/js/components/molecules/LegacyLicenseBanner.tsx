/**
 * Amber warning banner shown when a legacy license is active.
 *
 * @package StellarWP\Uplink
 */
import { __ } from '@wordpress/i18n';
import { AlertTriangle } from 'lucide-react';
import { useLicenseStore } from '@/stores/license-store';

/**
 * @since TBD
 */
export function LegacyLicenseBanner() {
    const hasLegacyLicense = useLicenseStore( ( s ) => s.hasLegacyLicense() );

    if ( ! hasLegacyLicense ) return null;

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
