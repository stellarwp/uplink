/**
 * License key input form.
 *
 * Wires activation to the stellarwp/uplink @wordpress/data store.
 * Inline error from store; success toast on completion.
 *
 * @package StellarWP\\Uplink
 */
import { useState, useEffect, useRef } from 'react';
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { KeyRound, Loader2 } from 'lucide-react';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { store as uplinkStore } from '@/store';
import { useToast } from '@/context/toast-context';

interface LicenseKeyInputProps {
    /** Called on successful activation (dialog can close) */
    onSuccess?: () => void;
    /** When set, fills the input with this value (e.g. from the test-key cheat-sheet) */
    prefillKey?: string;
}

/**
 * @since 3.0.0
 */
export function LicenseKeyInput( { onSuccess, prefillKey }: LicenseKeyInputProps ) {
    const [ key, setKey ] = useState( '' );
    const [ localError, setLocalError ] = useState<string | null>( null );

    const { activateLicense } = useDispatch( uplinkStore );
    const { addToast } = useToast();

    const isActivating = useSelect(
        ( select ) => select( uplinkStore ).isLicenseActivating(),
        [],
    );
    const activateError = useSelect(
        ( select ) => select( uplinkStore ).getActivateLicenseError(),
        [],
    );

    useEffect( () => {
        if ( prefillKey ) {
            setKey( prefillKey );
            setLocalError( null );
        }
    }, [ prefillKey ] );

    // Detect when activation finishes to show success toast or surface API error.
    const wasActivatingRef = useRef( false );
    useEffect( () => {
        const wasActivating = wasActivatingRef.current;
        wasActivatingRef.current = isActivating;

        if ( wasActivating && ! isActivating ) {
            if ( ! activateError ) {
                addToast( __( 'License activated successfully.', '%TEXTDOMAIN%' ), 'success' );
                setKey( '' );
                onSuccess?.();
            }
        }
    }, [ isActivating ] ); // eslint-disable-line react-hooks/exhaustive-deps

    const handleActivate = () => {
        const trimmedKey = key.trim();
        if ( ! trimmedKey ) {
            setLocalError( __( 'Please enter a license key.', '%TEXTDOMAIN%' ) );
            return;
        }
        setLocalError( null );
        activateLicense( trimmedKey );
    };

    const displayError = localError ?? activateError;

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
                        placeholder={ __( 'e.g. LWSW-UNIFIED-PRO-2025', '%TEXTDOMAIN%' ) }
                        value={ key }
                        onChange={ ( e ) => {
                            setKey( e.target.value.toUpperCase() );
                            if ( localError ) setLocalError( null );
                        } }
                        onKeyDown={ ( e ) => e.key === 'Enter' && ! isActivating && handleActivate() }
                        className="pl-10 font-mono uppercase"
                        aria-invalid={ !! displayError }
                        aria-describedby={ displayError ? 'license-key-error' : undefined }
                        disabled={ isActivating }
                    />
                </div>
                <Button onClick={ handleActivate } disabled={ isActivating || ! key.trim() }>
                    { isActivating ? (
                        <>
                            <Loader2 className="w-4 h-4 animate-spin" />
                            { __( 'Verifying…', '%TEXTDOMAIN%' ) }
                        </>
                    ) : (
                        __( 'Activate', '%TEXTDOMAIN%' )
                    ) }
                </Button>
            </div>
            { isActivating && (
                <p className="text-sm text-muted-foreground flex items-center gap-1.5">
                    <Loader2 className="w-3.5 h-3.5 animate-spin" />
                    { __( 'Checking license with server…', '%TEXTDOMAIN%' ) }
                </p>
            ) }
            { displayError && (
                <p id="license-key-error" className="text-sm text-destructive" role="alert">
                    { displayError }
                </p>
            ) }
        </div>
    );
}
