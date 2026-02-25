/**
 * Licenses tab content.
 *
 * Lists all activated licenses as LicenseCards.
 * Includes an "Add License" dialog with LicenseKeyInput.
 *
 * @package StellarWP\Uplink
 */
import { useState, useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import { Plus, KeyRound } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Dialog, DialogHeader, DialogContent } from '@/components/ui/dialog';
import { LicenseCard } from '@/components/molecules/LicenseCard';
import { LicenseKeyInput } from '@/components/molecules/LicenseKeyInput';
import { useLicenseStore } from '@/stores/license-store';
import { MOCK_LICENSES } from '@/data/licenses';
import { PRODUCTS } from '@/data/products';

interface LicenseListProps {
    /** When true, immediately opens the Add License dialog */
    openAddDialog?: boolean;
    /** Called when the externally-triggered dialog closes */
    onAddDialogClose?: () => void;
}

/**
 * @since TBD
 */
export function LicenseList( { openAddDialog = false, onAddDialogClose }: LicenseListProps = {} ) {
    const [ addOpen, setAddOpen ] = useState( false );
    const [ prefillKey, setPrefillKey ] = useState( '' );

    // Respond to external open request (e.g. from AppShell / MyProductsTab)
    useEffect( () => {
        if ( openAddDialog ) {
            setAddOpen( true );
        }
    }, [ openAddDialog ] );

    const handleClose = () => {
        setAddOpen( false );
        setPrefillKey( '' );
        onAddDialogClose?.();
    };

    const activeLicenses = useLicenseStore( ( s ) => s.activeLicenses );

    return (
        <div className="flex flex-col gap-4">
            <div className="flex items-center justify-between">
                <div>
                    <h2 className="text-base font-semibold text-foreground m-0">
                        { __( 'Your Licenses', '%TEXTDOMAIN%' ) }
                    </h2>
                    <p className="text-sm text-muted-foreground m-0">
                        { __( 'Manage the license keys associated with this site.', '%TEXTDOMAIN%' ) }
                    </p>
                </div>
                <Button onClick={ () => setAddOpen( true ) } size="sm">
                    <Plus />
                    { __( 'Add License', '%TEXTDOMAIN%' ) }
                </Button>
            </div>

            { activeLicenses.length === 0 ? (
                <Card className="flex flex-col items-center gap-3 py-12 text-center">
                    <KeyRound className="w-10 h-10 text-muted-foreground" />
                    <div>
                        <p className="font-medium text-foreground">
                            { __( 'No licenses yet', '%TEXTDOMAIN%' ) }
                        </p>
                        <p className="text-sm text-muted-foreground">
                            { __( 'Add a license key to get started.', '%TEXTDOMAIN%' ) }
                        </p>
                    </div>
                    <Button onClick={ () => setAddOpen( true ) } size="sm">
                        { __( 'Add License', '%TEXTDOMAIN%' ) }
                    </Button>
                </Card>
            ) : (
                <Card className="divide-y divide-border overflow-hidden p-0">
                    { activeLicenses.map( ( license ) => (
                        <LicenseCard key={ license.key } license={ license } />
                    ) ) }
                </Card>
            ) }

            {/* Add License dialog */}
            <Dialog open={ addOpen } onClose={ handleClose }>
                <DialogHeader
                    title={ __( 'Add License', '%TEXTDOMAIN%' ) }
                    description={ __( 'Enter your license key to activate it on this site.', '%TEXTDOMAIN%' ) }
                    onClose={ handleClose }
                />
                <DialogContent>
                    <LicenseKeyInput onSuccess={ handleClose } prefillKey={ prefillKey } />

                    { /* Dev-only: test key cheat-sheet with click-to-fill */ }
                    { process.env.NODE_ENV !== 'production' && (
                        <details className="mt-4 rounded border border-dashed border-amber-300 bg-amber-50 p-3 text-xs">
                            <summary className="cursor-pointer font-medium text-amber-700 select-none">
                                { __( 'Dev: test license keys (click to fill)', '%TEXTDOMAIN%' ) }
                            </summary>
                            <table className="mt-2 w-full border-collapse text-amber-900">
                                <thead>
                                    <tr className="border-b border-amber-200">
                                        <th className="py-1 pr-3 text-left font-medium">{ __( 'Key', '%TEXTDOMAIN%' ) }</th>
                                        <th className="py-1 pr-3 text-left font-medium">{ __( 'Tier', '%TEXTDOMAIN%' ) }</th>
                                        <th className="py-1 text-left font-medium">{ __( 'Products', '%TEXTDOMAIN%' ) }</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    { MOCK_LICENSES.map( ( l ) => (
                                        <tr
                                            key={ l.key }
                                            className="border-b border-amber-100 last:border-0 cursor-pointer hover:bg-amber-100 transition-colors"
                                            onClick={ () => setPrefillKey( l.key ) }
                                            title={ __( 'Click to fill', '%TEXTDOMAIN%' ) }
                                        >
                                            <td className="py-1 pr-3 font-mono">{ l.key }</td>
                                            <td className="py-1 pr-3">
                                                { l.tier }
                                                { l.isExpired ? ' (expired)' : '' }
                                            </td>
                                            <td className="py-1">
                                                { l.productSlugs
                                                    .map( ( s ) => PRODUCTS.find( ( p ) => p.slug === s )?.name ?? s )
                                                    .join( ', ' ) }
                                            </td>
                                        </tr>
                                    ) ) }
                                </tbody>
                            </table>
                        </details>
                    ) }
                </DialogContent>
            </Dialog>
        </div>
    );
}
