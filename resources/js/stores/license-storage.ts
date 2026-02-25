/**
 * Storage adapter for the license store.
 *
 * Currently backed by localStorage as a temporary persistence layer.
 *
 * To migrate to the REST API, replace the three methods below with
 * fetch calls to the uplink/v1 endpoints. The persist middleware
 * supports async (Promise-returning) implementations, so no changes
 * to the store itself are required.
 *
 * @see https://docs.pmnd.rs/zustand/integrations/persisting-store-data#custom-storage
 * @package StellarWP\Uplink
 */
/**
 * @todo Replace with REST API calls once the persistence layer is ready.
 *       getItem    → GET    /wp-json/uplink/v1/licenses
 *       setItem    → POST   /wp-json/uplink/v1/licenses
 *       removeItem → DELETE /wp-json/uplink/v1/licenses
 *
 * The methods are async-compatible (may return Promises), so an API-backed
 * implementation requires no changes to the store.
 */
export const licenseStorage = {
    getItem:    ( name: string ) => localStorage.getItem( name ),
    setItem:    ( name: string, value: string ) => localStorage.setItem( name, value ),
    removeItem: ( name: string ) => localStorage.removeItem( name ),
};
