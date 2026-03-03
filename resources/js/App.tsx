/**
 * @see .plans/wp-data-store-features.md for the full migration checklist.
 */
import { AppShell } from '@/components/templates/AppShell';
import { Toaster } from '@/components/ui/toast';
import { ErrorBoundary } from '@/components/ErrorBoundary';

export const App = () => {
    return (
        <ErrorBoundary>
            <AppShell />
            <Toaster />
        </ErrorBoundary>
    );
};
