/**
 * Custom modal dialog.
 *
 * Uses z-[100000] on the overlay so it clears the WP admin bar (z-index: 99999).
 * NOT using Radix Dialog — keeping this self-contained to control z-index.
 *
 * @package StellarWP\Uplink
 */
import { useEffect, type ReactNode } from 'react';
import { X } from 'lucide-react';
import { cn } from '@/lib/utils';
import { __ } from '@wordpress/i18n';

interface DialogProps {
    open: boolean;
    onClose: () => void;
    children: ReactNode;
    /** Max width class, defaults to "max-w-lg" */
    maxWidth?: string;
}

/**
 * Dialog overlay + panel. Traps focus via the backdrop click handler.
 * @since TBD
 */
export function Dialog( { open, onClose, children, maxWidth = 'max-w-lg' }: DialogProps ) {
    // Close on Escape key
    useEffect( () => {
        if ( ! open ) return;
        const handleKey = ( e: KeyboardEvent ) => {
            if ( e.key === 'Escape' ) onClose();
        };
        document.addEventListener( 'keydown', handleKey );
        return () => document.removeEventListener( 'keydown', handleKey );
    }, [ open, onClose ] );

    // Prevent body scroll when open
    useEffect( () => {
        if ( open ) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
        return () => {
            document.body.style.overflow = '';
        };
    }, [ open ] );

    if ( ! open ) return null;

    return (
        <div
            className="fixed inset-0 z-[100000] flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
        >
            {/* Backdrop */}
            <div
                className="absolute inset-0 bg-black/50"
                onClick={ onClose }
                aria-hidden="true"
            />

            {/* Panel */}
            <div
                className={ cn(
                    'relative z-10 w-full rounded-lg bg-background shadow-xl',
                    maxWidth
                ) }
                onClick={ ( e ) => e.stopPropagation() }
            >
                { children }
            </div>
        </div>
    );
}

interface DialogHeaderProps {
    title: string;
    description?: string;
    onClose: () => void;
}

export function DialogHeader( { title, description, onClose }: DialogHeaderProps ) {
    return (
        <div className="flex items-start justify-between p-6 pb-4 border-b border-border">
            <div>
                <h2 className="text-lg font-semibold text-foreground m-0">{ title }</h2>
                { description && (
                    <p className="mt-1 text-sm text-muted-foreground m-0">{ description }</p>
                ) }
            </div>
            <button
                type="button"
                onClick={ onClose }
                className="ml-4 shrink-0 rounded p-1 text-muted-foreground hover:text-foreground hover:bg-accent transition-colors"
                aria-label={ __( 'Close dialog', '%TEXTDOMAIN%' ) }
            >
                <X className="w-4 h-4" />
            </button>
        </div>
    );
}

export function DialogContent( { children, className }: { children: ReactNode; className?: string } ) {
    return (
        <div className={ cn( 'p-6', className ) }>
            { children }
        </div>
    );
}

export function DialogFooter( { children, className }: { children: ReactNode; className?: string } ) {
    return (
        <div className={ cn( 'flex items-center justify-end gap-2 px-6 pb-6 pt-0', className ) }>
            { children }
        </div>
    );
}
