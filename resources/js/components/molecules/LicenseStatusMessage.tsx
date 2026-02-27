import { CheckCircle, AlertTriangle } from 'lucide-react';
import { cn } from '@/lib/utils';

/** @since 3.0.0 */
export type LicenseMessageType = 'success' | 'error' | 'idle';

interface LicenseStatusMessageProps {
    type: LicenseMessageType;
    message: string;
}

/**
 * @since 3.0.0
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
            { isSuccess ? (
                <CheckCircle className="w-4 h-4 shrink-0" />
            ) : (
                <AlertTriangle className="w-4 h-4 shrink-0" />
            ) }
            { message }
        </p>
    );
}
