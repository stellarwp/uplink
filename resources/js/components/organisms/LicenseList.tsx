/**
 * Licenses tab content.
 *
 * Lists all activated licenses as LicenseCards.
 * Includes an "Activate a License" dialog with LicenseKeyInput.
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
    const [ prefillKey, setPrefillKey ] = useState( '' );

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

            {/* Activate a License dialog */}
            <Dialog open={ addOpen } onClose={ handleClose }>
                <DialogHeader
                    title={ __( 'Activate a License', '%TEXTDOMAIN%' ) }
                    description={ __( 'Enter your license key to unlock products and features. New customers receive a unified key; legacy customers can use their existing per-product keys.', '%TEXTDOMAIN%' ) }
                    onClose={ handleClose }
                />
                <DialogContent>
                    <LicenseKeyInput onSuccess={ handleClose } prefillKey={ prefillKey } />

                    { /* Dev-only: test key cheat-sheet with click-to-fill */ }
                    { process.env.NODE_ENV !== 'production' && (
                        <div className="mt-6 pt-4 border-t">
                            <p className="text-xs font-medium text-muted-foreground mb-2">
                                { __( 'Test License Keys', '%TEXTDOMAIN%' ) }
                            </p>
                            <div className="space-y-1.5 text-xs text-muted-foreground">

                                <p className="font-medium text-foreground text-[11px] uppercase tracking-wide mt-2">
                                    { __( 'Unified (all products)', '%TEXTDOMAIN%' ) }
                                </p>
                                <div className="grid grid-cols-[1fr_auto] gap-x-3">
                                    { [
                                        [ 'LW-UNIFIED-BASIC-2025',   __( 'Basic', '%TEXTDOMAIN%' ) ],
                                        [ 'LW-UNIFIED-PRO-2025',     __( 'Pro', '%TEXTDOMAIN%' ) ],
                                        [ 'LW-UNIFIED-AGENCY-2025',  __( 'Agency', '%TEXTDOMAIN%' ) ],
                                        [ 'LW-UNIFIED-PRO-EXPIRED',  __( 'Pro (expired)', '%TEXTDOMAIN%' ) ],
                                    ].map( ( [ key, label ] ) => (
                                        <>
                                            <code
                                                key={ key }
                                                className="font-mono bg-muted px-1 rounded cursor-pointer hover:bg-muted/70 transition-colors py-0.5"
                                                onClick={ () => setPrefillKey( key ) }
                                                title={ __( 'Click to fill', '%TEXTDOMAIN%' ) }
                                            >
                                                { key }
                                            </code>
                                            <span className="self-center">{ label }</span>
                                        </>
                                    ) ) }
                                </div>

                                <p className="font-medium text-foreground text-[11px] uppercase tracking-wide mt-2">
                                    { __( 'Unified (single product)', '%TEXTDOMAIN%' ) }
                                </p>
                                <div className="grid grid-cols-[1fr_auto] gap-x-3">
                                    { [
                                        [ 'LW-UNIFIED-KAD-PRO-2025',   __( 'Kadence Pro', '%TEXTDOMAIN%' ) ],
                                        [ 'LW-UNIFIED-GIVE-BASIC-2025', __( 'GiveWP Basic', '%TEXTDOMAIN%' ) ],
                                        [ 'LW-UNIFIED-KAD-GIVE-2025',   __( 'Kadence + GiveWP Pro', '%TEXTDOMAIN%' ) ],
                                    ].map( ( [ key, label ] ) => (
                                        <>
                                            <code
                                                key={ key }
                                                className="font-mono bg-muted px-1 rounded cursor-pointer hover:bg-muted/70 transition-colors py-0.5"
                                                onClick={ () => setPrefillKey( key ) }
                                                title={ __( 'Click to fill', '%TEXTDOMAIN%' ) }
                                            >
                                                { key }
                                            </code>
                                            <span className="self-center">{ label }</span>
                                        </>
                                    ) ) }
                                </div>

                                <p className="font-medium text-foreground text-[11px] uppercase tracking-wide mt-2">
                                    { __( 'Legacy (per-product)', '%TEXTDOMAIN%' ) }
                                </p>
                                <div className="grid grid-cols-[1fr_auto] gap-x-3">
                                    { [
                                        [ 'LD-LEGACY-AGENCY-001',  __( 'LearnDash Agency', '%TEXTDOMAIN%' ) ],
                                        [ 'GIVE-LEGACY-PRO-001',   __( 'GiveWP Pro', '%TEXTDOMAIN%' ) ],
                                        [ 'TEC-LEGACY-PRO-001',    __( 'Events Calendar Pro', '%TEXTDOMAIN%' ) ],
                                        [ 'KAD-LEGACY-PRO-001',    __( 'Kadence Pro', '%TEXTDOMAIN%' ) ],
                                    ].map( ( [ key, label ] ) => (
                                        <>
                                            <code
                                                key={ key }
                                                className="font-mono bg-muted px-1 rounded cursor-pointer hover:bg-muted/70 transition-colors py-0.5"
                                                onClick={ () => setPrefillKey( key ) }
                                                title={ __( 'Click to fill', '%TEXTDOMAIN%' ) }
                                            >
                                                { key }
                                            </code>
                                            <span className="self-center">{ label }</span>
                                        </>
                                    ) ) }
                                </div>

                            </div>
                        </div>
                    ) }
                </DialogContent>
            </Dialog>
        </div>
    );
}
