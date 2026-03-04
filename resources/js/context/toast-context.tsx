/**
 * Toast notification context — replaces Zustand toast-store.ts.
 *
 * Mount <ToastProvider> once in App.tsx; consume with useToast() anywhere
 * in the component tree.
 *
 * @see .plans/wp-data-store-features.md
 * @package StellarWP\Uplink
 */
import { createContext, useCallback, useContext, useState, type ReactNode } from 'react';

export type ToastVariant = 'default' | 'success' | 'error' | 'warning';

export interface Toast {
    id:      string;
    message: string;
    variant: ToastVariant;
}

interface ToastContextValue {
    toasts:      Toast[];
    addToast:    ( message: string, variant?: ToastVariant ) => void;
    removeToast: ( id: string ) => void;
}

const ToastContext = createContext<ToastContextValue>( {
    toasts:      [],
    addToast:    () => {},
    removeToast: () => {},
} );

/**
 * @since 3.0.0
 */
export function ToastProvider( { children }: { children: ReactNode } ) {
    const [ toasts, setToasts ] = useState<Toast[]>( [] );

    const removeToast = useCallback( ( id: string ) => {
        setToasts( ( prev ) => prev.filter( ( t ) => t.id !== id ) );
    }, [] );

    const addToast = useCallback(
        ( message: string, variant: ToastVariant = 'default' ) => {
            const id = crypto.randomUUID();
            setToasts( ( prev ) => [ ...prev, { id, message, variant } ] );
            setTimeout( () => removeToast( id ), 3500 );
        },
        [ removeToast ],
    );

    return (
        <ToastContext.Provider value={ { toasts, addToast, removeToast } }>
            { children }
        </ToastContext.Provider>
    );
}

/**
 * @since 3.0.0
 */
export const useToast = () => useContext( ToastContext );
