/**
 * Mock license database.
 *
 * Test license keys used by the license store for simulated verification.
 * Keys match the cheat-sheet shown in the Add License dialog.
 * In production this data comes from the uplink/v1 REST API.
 *
 * @package StellarWP\Uplink
 */
import type { License } from '@/types/api';

export const MOCK_LICENSES: License[] = [
    // -----------------------------------------------------------------------
    // Unified — all products
    // -----------------------------------------------------------------------
    {
        key: 'LW-UNIFIED-BASIC-2025',
        type: 'unified',
        tier: 'starter',
        productSlugs: [ 'givewp', 'the-events-calendar', 'learndash', 'kadence' ],
        expires: 'December 31, 2026',
        isExpired: false,
        renewUrl: 'https://liquidweb.com/renew/',
    },
    {
        key: 'LW-UNIFIED-PRO-2025',
        type: 'unified',
        tier: 'pro',
        productSlugs: [ 'givewp', 'the-events-calendar', 'learndash', 'kadence' ],
        expires: 'December 31, 2026',
        isExpired: false,
        renewUrl: 'https://liquidweb.com/renew/',
    },
    {
        key: 'LW-UNIFIED-AGENCY-2025',
        type: 'unified',
        tier: 'agency',
        productSlugs: [ 'givewp', 'the-events-calendar', 'learndash', 'kadence' ],
        expires: 'December 31, 2026',
        isExpired: false,
        renewUrl: 'https://liquidweb.com/renew/',
    },
    {
        key: 'LW-UNIFIED-PRO-EXPIRED',
        type: 'unified',
        tier: 'pro',
        productSlugs: [ 'givewp', 'the-events-calendar', 'learndash', 'kadence' ],
        expires: 'January 1, 2025',
        isExpired: true,
        renewUrl: 'https://liquidweb.com/renew/',
    },

    // -----------------------------------------------------------------------
    // Unified — single product
    // -----------------------------------------------------------------------
    {
        key: 'LW-UNIFIED-KAD-PRO-2025',
        type: 'unified',
        tier: 'pro',
        productSlugs: [ 'kadence' ],
        expires: 'December 31, 2026',
        isExpired: false,
        renewUrl: 'https://kadencewp.com/renew/',
    },
    {
        key: 'LW-UNIFIED-GIVE-BASIC-2025',
        type: 'unified',
        tier: 'starter',
        productSlugs: [ 'givewp' ],
        expires: 'December 31, 2026',
        isExpired: false,
        renewUrl: 'https://givewp.com/renew/',
    },
    {
        key: 'LW-UNIFIED-KAD-GIVE-2025',
        type: 'unified',
        tier: 'pro',
        productSlugs: [ 'kadence', 'givewp' ],
        expires: 'December 31, 2026',
        isExpired: false,
        renewUrl: 'https://liquidweb.com/renew/',
    },

    // -----------------------------------------------------------------------
    // Legacy — per-product
    // -----------------------------------------------------------------------
    {
        key: 'LD-LEGACY-AGENCY-001',
        type: 'legacy',
        tier: 'agency',
        productSlugs: [ 'learndash' ],
        expires: 'December 31, 2026',
        isExpired: false,
        renewUrl: 'https://learndash.com/renew/',
    },
    {
        key: 'GIVE-LEGACY-PRO-001',
        type: 'legacy',
        tier: 'pro',
        productSlugs: [ 'givewp' ],
        expires: 'December 31, 2026',
        isExpired: false,
        renewUrl: 'https://givewp.com/renew/',
    },
    {
        key: 'TEC-LEGACY-PRO-001',
        type: 'legacy',
        tier: 'pro',
        productSlugs: [ 'the-events-calendar' ],
        expires: 'December 31, 2026',
        isExpired: false,
        renewUrl: 'https://evnt.is/renew',
    },
    {
        key: 'KAD-LEGACY-PRO-001',
        type: 'legacy',
        tier: 'pro',
        productSlugs: [ 'kadence' ],
        expires: 'December 31, 2026',
        isExpired: false,
        renewUrl: 'https://kadencewp.com/renew/',
    },
];

/** Look up a license by key. Returns undefined if not found (invalid key). */
export function findLicense( key: string ): License | undefined {
    return MOCK_LICENSES.find( ( l ) => l.key === key );
}
