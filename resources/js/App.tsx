/**
 */
import { AppShell } from '@/components/templates/AppShell';
import { Toaster } from '@/components/ui/toast';
import { ErrorBoundary } from '@/components/ErrorBoundary';
import { ToastProvider } from '@/context/toast-context';
import { FilterProvider } from '@/context/filter-context';

export const App = () => {
    return (
        <ToastProvider>
            <FilterProvider>
                <ErrorBoundary>
                    <AppShell />
                    <Toaster />
                </ErrorBoundary>
            </FilterProvider>
        </ToastProvider>
    );
};
