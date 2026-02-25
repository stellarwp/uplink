/**
 * License key input form.
 *
 * 1200ms simulated verification delay, inline error display, success toast.
 *
 * @package StellarWP\Uplink
 */
import { useState, useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import { KeyRound, Loader2 } from 'lucide-react';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { useLicenseStore } from '@/stores/license-store';
import { useToastStore } from '@/stores/toast-store';

interface LicenseKeyInputProps {
    /** Called on successful activation (dialog can close) */
    onSuccess?: () => void;
    /** When set, fills the input with this value (e.g. from the test-key cheat-sheet) */
    prefillKey?: string;
}

/**
 * @since TBD
 */
export function LicenseKeyInput( { onSuccess, prefillKey }: LicenseKeyInputProps ) {
    const [ key, setKey ] = useState( '' );
    const [ error, setError ] = useState<string | null>( null );
    const [ isVerifying, setIsVerifying ] = useState( false );

    const { activateLicense } = useLicenseStore();
    const { addToast } = useToastStore();

    useEffect( () => {
        if ( prefillKey ) {
            setKey( prefillKey );
            setError( null );
        }
    }, [ prefillKey ] );

    const handleActivate = async () => {
        const trimmedKey = key.trim();
        if ( ! trimmedKey ) {
            setError( __( 'Please enter a license key.', '%TEXTDOMAIN%' ) );
            return;
        }

        setError( null );
        setIsVerifying( true );

        const errorMsg = await activateLicense( trimmedKey );

        setIsVerifying( false );

        if ( errorMsg ) {
            setError( errorMsg );
            addToast( errorMsg, 'error' );
            return;
        }

        addToast( __( 'License activated successfully.', '%TEXTDOMAIN%' ), 'success' );
        setKey( '' );
        onSuccess?.();
    };

    return (
        <div className="space-y-3">
            <label className="text-sm font-medium" htmlFor="license-key-input">
                { __( 'Enter License Key', '%TEXTDOMAIN%' ) }
            </label>
            <div className="flex gap-2">
                <div className="relative flex-1">
                    <KeyRound className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground pointer-events-none" />
                    <Input
                        id="license-key-input"
                        placeholder={ __( 'e.g. LW-UNIFIED-PRO-2025', '%TEXTDOMAIN%' ) }
                        value={ key }
                        onChange={ ( e ) => {
                            setKey( e.target.value );
                            if ( error ) setError( null );
                        } }
                        onKeyDown={ ( e ) => e.key === 'Enter' && ! isVerifying && handleActivate() }
                        className="pl-10 font-mono"
                        aria-invalid={ !! error }
                        aria-describedby={ error ? 'license-key-error' : undefined }
                        disabled={ isVerifying }
                    />
                </div>
                <Button onClick={ handleActivate } disabled={ isVerifying || ! key.trim() }>
                    { isVerifying ? (
                        <>
                            <Loader2 className="w-4 h-4 animate-spin" />
                            { __( 'Verifying…', '%TEXTDOMAIN%' ) }
                        </>
                    ) : (
                        __( 'Activate', '%TEXTDOMAIN%' )
                    ) }
                </Button>
            </div>
            { isVerifying && (
                <p className="text-sm text-muted-foreground flex items-center gap-1.5">
                    <Loader2 className="w-3.5 h-3.5 animate-spin" />
                    { __( 'Checking license with server…', '%TEXTDOMAIN%' ) }
                </p>
            ) }
            { error && (
                <p id="license-key-error" className="text-sm text-destructive" role="alert">
                    { error }
                </p>
            ) }
        </div>
    );
}
