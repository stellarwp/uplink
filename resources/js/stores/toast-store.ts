/**
 * Zustand store for toast notifications.
 * Replaced by `context/toast-context.tsx` (React Context + useState) when Zustand is removed.
 *
 * @see .plans/rest-api-react-query-migration.md for the full migration checklist.
 * @package StellarWP\Uplink
 */
import { create } from 'zustand';

export type ToastVariant = 'default' | 'success' | 'error' | 'warning';

export interface Toast {
    id: string;
    message: string;
    variant: ToastVariant;
}

interface ToastStoreState {
    toasts: Toast[];
    addToast: ( message: string, variant?: ToastVariant ) => void;
    removeToast: ( id: string ) => void;
}

let toastIdCounter = 0;

export const useToastStore = create<ToastStoreState>( ( set ) => ( {
    toasts: [],

    addToast: ( message, variant = 'default' ) => {
        const id = `toast-${ ++toastIdCounter }`;
        set( ( state ) => ( {
            toasts: [ ...state.toasts, { id, message, variant } ],
        } ) );

        // Auto-dismiss after 3.5s
        setTimeout( () => {
            set( ( state ) => ( {
                toasts: state.toasts.filter( ( t ) => t.id !== id ),
            } ) );
        }, 3500 );
    },

    removeToast: ( id ) => {
        set( ( state ) => ( {
            toasts: state.toasts.filter( ( t ) => t.id !== id ),
        } ) );
    },
} ) );
