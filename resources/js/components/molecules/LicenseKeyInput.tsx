/**
 * License key input form.
 *
 * 1200ms simulated verification delay, inline error display, success toast.
 *
 * @package StellarWP\Uplink
 */
import { useState } from 'react';
import { __ } from '@wordpress/i18n';
import { Loader2 } from 'lucide-react';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { useLicenseStore } from '@/stores/license-store';
import { useToastStore } from '@/stores/toast-store';

interface LicenseKeyInputProps {
    /** Called on successful activation (dialog can close) */
    onSuccess?: () => void;
}

/**
 * @since TBD
 */
export function LicenseKeyInput( { onSuccess }: LicenseKeyInputProps ) {
    const [ key, setKey ] = useState( '' );
    const [ error, setError ] = useState<string | null>( null );
    const [ isVerifying, setIsVerifying ] = useState( false );

    const { activateLicense } = useLicenseStore();
    const { addToast } = useToastStore();

    const handleSubmit = async ( e: React.FormEvent ) => {
        e.preventDefault();

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
        <form onSubmit={ handleSubmit } className="flex flex-col gap-3">
            <div className="flex gap-2">
                <Input
                    id="license-key-input"
                    placeholder="XXXX-XXXX-XXXX-XXXX"
                    value={ key }
                    onChange={ ( e ) => {
                        setKey( e.target.value );
                        if ( error ) setError( null );
                    } }
                    aria-invalid={ !! error }
                    aria-describedby={ error ? 'license-key-error' : undefined }
                    disabled={ isVerifying }
                    className="flex-1"
                />
                <Button type="submit" disabled={ isVerifying || ! key.trim() }>
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
            { error && (
                <p
                    id="license-key-error"
                    className="text-destructive text-sm"
                    role="alert"
                >
                    { error }
                </p>
            ) }
        </form>
    );
}
