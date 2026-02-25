/**
 * Toast notification renderer.
 *
 * Reads from useToastStore and renders a fixed bottom-right stack.
 * Auto-dismiss is handled by the store (3.5s).
 *
 * @package StellarWP\Uplink
 */
import { X, CheckCircle, AlertTriangle, Info } from 'lucide-react';
import { __ } from '@wordpress/i18n';
import { cn } from '@/lib/utils';
import { useToastStore, type ToastVariant } from '@/stores/toast-store';

const VARIANT_STYLES: Record<ToastVariant, string> = {
    default: 'bg-background border border-border text-foreground',
    success: 'bg-green-50 border border-green-200 text-green-800',
    error: 'bg-red-50 border border-red-200 text-red-800',
    warning: 'bg-amber-50 border border-amber-200 text-amber-800',
};

function ToastIcon( { variant }: { variant: ToastVariant } ) {
    if ( variant === 'success' ) return <CheckCircle className="w-4 h-4 shrink-0" />;
    if ( variant === 'error' ) return <AlertTriangle className="w-4 h-4 shrink-0" />;
    if ( variant === 'warning' ) return <AlertTriangle className="w-4 h-4 shrink-0" />;
    return <Info className="w-4 h-4 shrink-0" />;
}

/**
 * Renders the toast stack. Mount as a sibling of AppShell in App.tsx.
 * @since TBD
 */
export function Toaster() {
    const { toasts, removeToast } = useToastStore();

    if ( toasts.length === 0 ) return null;

    return (
        <div className="fixed bottom-4 right-4 z-[100001] flex flex-col gap-2 pointer-events-none">
            { toasts.map( ( toast ) => (
                <div
                    key={ toast.id }
                    role="status"
                    aria-live="polite"
                    className={ cn(
                        'pointer-events-auto flex items-start gap-3 rounded-lg px-4 py-3 shadow-lg text-sm max-w-xs',
                        VARIANT_STYLES[ toast.variant ]
                    ) }
                >
                    <ToastIcon variant={ toast.variant } />
                    <span className="flex-1">{ toast.message }</span>
                    <button
                        type="button"
                        onClick={ () => removeToast( toast.id ) }
                        className="shrink-0 opacity-60 hover:opacity-100 transition-opacity"
                        aria-label={ __( 'Dismiss notification', '%TEXTDOMAIN%' ) }
                    >
                        <X className="w-3.5 h-3.5" />
                    </button>
                </div>
            ) ) }
        </div>
    );
}
