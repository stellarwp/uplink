/**
 * @TODO (step 5): Once the REST API is active, import `useEffect` from 'react' and
 *                 `useLicenseStore` from '@/stores/license-store', then call initialize()
 *                 on mount so the store hydrates from the API instead of localStorage:
 *
 *                   import { useEffect } from 'react';
 *                   import { useLicenseStore } from '@/stores/license-store';
 *
 *                   export const App = () => {
 *                       useEffect( () => {
 *                           useLicenseStore.getState().initialize();
 *                       }, [] );
 *
 *                       return ( <> <AppShell /> <Toaster /> </> );
 *                   };
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
