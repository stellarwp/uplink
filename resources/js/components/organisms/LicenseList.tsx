/**
 * Licenses tab content.
 *
 * Shows the single active unified license key (or an empty state) and
 * an "Add License" dialog with LicenseKeyInput.
 *
 * @see .plans/wp-data-store-licenses.md
 * @package StellarWP\\Uplink
 */
import { useState, useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { Plus, KeyRound } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Dialog, DialogHeader, DialogContent } from '@/components/ui/dialog';
import { LicenseCard } from '@/components/molecules/LicenseCard';
import { LicenseKeyInput } from '@/components/molecules/LicenseKeyInput';
import { store as uplinkStore } from '@/store';

interface LicenseListProps {
    /** When true, immediately opens the Add License dialog */
    openAddDialog?: boolean;
    /** Called when the externally-triggered dialog closes */
    onAddDialogClose?: () => void;
}

/**
 * @since 3.0.0
 */
export function LicenseList( { openAddDialog = false, onAddDialogClose }: LicenseListProps = {} ) {
    const [ addOpen, setAddOpen ] = useState( false );

    useEffect( () => {
        if ( openAddDialog ) {
            setAddOpen( true );
        }
    }, [ openAddDialog ] );

    const handleClose = () => {
        setAddOpen( false );
        onAddDialogClose?.();
    };

    // Calling getLicense() inside useSelect triggers the getLicense resolver.
    const licenseKey = useSelect(
        ( select ) => select( uplinkStore ).getLicense(),
        [],
    );

    return (
        <div className="flex flex-col gap-4">
            <div className="flex items-center justify-between">
                <div>
                    <h2 className="text-base font-semibold text-foreground m-0">
                        { __( 'Your License', '%TEXTDOMAIN%' ) }
                    </h2>
                    <p className="text-sm text-muted-foreground m-0">
                        { __( 'Manage the license key associated with this site.', '%TEXTDOMAIN%' ) }
                    </p>
                </div>
                { ! licenseKey && (
                    <Button onClick={ () => setAddOpen( true ) } size="sm">
                        <Plus />
                        { __( 'Add License', '%TEXTDOMAIN%' ) }
                    </Button>
                ) }
            </div>

            { licenseKey ? (
                <Card className="overflow-hidden p-0">
                    <LicenseCard licenseKey={ licenseKey } />
                </Card>
            ) : (
                <Card className="flex flex-col items-center gap-3 py-12 text-center">
                    <KeyRound className="w-10 h-10 text-muted-foreground" />
                    <div>
                        <p className="font-medium text-foreground">
                            { __( 'No license yet', '%TEXTDOMAIN%' ) }
                        </p>
                        <p className="text-sm text-muted-foreground">
                            { __( 'Add a license key to get started.', '%TEXTDOMAIN%' ) }
                        </p>
                    </div>
                    <Button onClick={ () => setAddOpen( true ) } size="sm">
                        { __( 'Add License', '%TEXTDOMAIN%' ) }
                    </Button>
                </Card>
            ) }

            {/* Activate a License dialog */}
            <Dialog open={ addOpen } onClose={ handleClose }>
                <DialogHeader
                    title={ __( 'Activate a License', '%TEXTDOMAIN%' ) }
                    description={ __( 'Enter your license key to unlock products and features.', '%TEXTDOMAIN%' ) }
                    onClose={ handleClose }
                />
                <DialogContent>
                    <LicenseKeyInput onSuccess={ handleClose } />
                </DialogContent>
            </Dialog>
        </div>
    );
}
