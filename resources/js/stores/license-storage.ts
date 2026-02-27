/**
 * Storage adapter for the Zustand persist middleware.
 * Deleted when the persist middleware is removed from license-store.ts.
 *
 * @see .plans/rest-api-react-query-migration.md for the full migration checklist.
 * @package StellarWP\Uplink
 */
export const licenseStorage = {
    getItem:    ( name: string ) => localStorage.getItem( name ),
    setItem:    ( name: string, value: string ) => localStorage.setItem( name, value ),
    removeItem: ( name: string ) => localStorage.removeItem( name ),
};
