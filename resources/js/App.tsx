/**
 * @see .plans/rest-api-react-query-migration.md for the full migration checklist.
 */
import { AppShell } from '@/components/templates/AppShell';
import { Toaster } from '@/components/ui/toast';

export const App = () => {
    return (
        <>
            <AppShell />
            <Toaster />
        </>
    );
};
