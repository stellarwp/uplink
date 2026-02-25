/**
 * Storage adapter for the Zustand persist middleware.
 *
 * Wraps localStorage so the persist middleware can be swapped for the REST API
 * without touching the store. This file exists only while the mock persistence
 * layer is in use.
 *
 * @TODO (step 6): Delete this entire file once the `persist` middleware has been
 *                 removed from `stores/license-store.ts`. No other file imports it.
 *
 * @package StellarWP\Uplink
 */
export const licenseStorage = {
    getItem:    ( name: string ) => localStorage.getItem( name ),
    setItem:    ( name: string, value: string ) => localStorage.setItem( name, value ),
    removeItem: ( name: string ) => localStorage.removeItem( name ),
};
