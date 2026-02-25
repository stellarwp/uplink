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

    // Respond to external open request (e.g. from AppShell / MyProductsTab)
    useEffect( () => {
        if ( openAddDialog ) {
            setAddOpen( true );
        }
    }, [ openAddDialog ] );

    const handleClose = () => {
        setAddOpen( false );
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
                    <LicenseKeyInput onSuccess={ handleClose } />

                    { /* Dev-only: quick test keys reminder */ }
                    { process.env.NODE_ENV !== 'production' && (
                        <p className="mt-3 text-xs text-muted-foreground">
                            { __( 'Dev tip: open the detail panel on any license card to see available test keys.', '%TEXTDOMAIN%' ) }
                        </p>
                    ) }
                </DialogContent>
            </Dialog>
        </div>
    );
}
