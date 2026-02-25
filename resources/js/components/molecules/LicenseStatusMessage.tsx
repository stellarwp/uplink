import { Icon, check, caution } from '@wordpress/icons';
import { cn } from '@/lib/utils';

/** @since TBD */
export type LicenseMessageType = 'success' | 'error' | 'idle';

interface LicenseStatusMessageProps {
    type: LicenseMessageType;
    message: string;
}

/**
 * @since TBD
 */
export function LicenseStatusMessage( { type, message }: LicenseStatusMessageProps ) {
    if ( type === 'idle' ) {
        return null;
    }

    const isSuccess = type === 'success';

    return (
        <p
            className={ cn(
                'mt-3 flex items-center gap-2 text-xs',
                isSuccess ? 'text-green-600' : 'text-red-600'
            ) }
            role="status"
            aria-live="polite"
        >
            <Icon icon={ isSuccess ? check : caution } size={ 16 } />
            { message }
        </p>
    );
}
