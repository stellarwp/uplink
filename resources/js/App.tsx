/**
 * @see .plans/wp-data-store-features.md for the full migration checklist.
 */
import { AppShell } from '@/components/templates/AppShell';
import { Toaster } from '@/components/ui/toast';
import { ErrorBoundary } from '@/components/ErrorBoundary';
import { ToastProvider } from '@/context/toast-context';

export const App = () => {
    return (
        <ToastProvider>
            <ErrorBoundary>
                <AppShell />
                <Toaster />
            </ErrorBoundary>
        </ToastProvider>
    );
};
