/**
 * Mock license database.
 *
 * 11 test license keys used by the license store for simulated verification.
 * In production this data comes from the uplink/v1 REST API.
 *
 * @package StellarWP\Uplink
 */
import type { License } from '@/types/api';

export const MOCK_LICENSES: License[] = [
    // Unified keys — cover all products at the stated tier
    {
        key: 'LW-UNIFIED-STARTER-2025',
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
        key: 'LW-UNIFIED-EXPIRED-001',
        type: 'unified',
        tier: 'pro',
        productSlugs: [ 'givewp', 'the-events-calendar', 'learndash', 'kadence' ],
        expires: 'January 1, 2025',
        isExpired: true,
        renewUrl: 'https://liquidweb.com/renew/',
    },

    // GiveWP-specific keys
    {
        key: 'GIVE-STARTER-2025-001',
        type: 'unified',
        tier: 'starter',
        productSlugs: [ 'givewp' ],
        expires: 'December 31, 2026',
        isExpired: false,
        renewUrl: 'https://givewp.com/renew/',
    },
    {
        key: 'GIVE-PRO-2025-001',
        type: 'unified',
        tier: 'pro',
        productSlugs: [ 'givewp' ],
        expires: 'December 31, 2026',
        isExpired: false,
        renewUrl: 'https://givewp.com/renew/',
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

    // Events Calendar key
    {
        key: 'TEC-PRO-2025-001',
        type: 'unified',
        tier: 'pro',
        productSlugs: [ 'the-events-calendar' ],
        expires: 'December 31, 2026',
        isExpired: false,
        renewUrl: 'https://evnt.is/renew',
    },

    // LearnDash key
    {
        key: 'LD-AGENCY-2025-001',
        type: 'unified',
        tier: 'agency',
        productSlugs: [ 'learndash' ],
        expires: 'December 31, 2026',
        isExpired: false,
        renewUrl: 'https://learndash.com/renew/',
    },

    // Kadence key
    {
        key: 'KAD-PRO-2025-001',
        type: 'unified',
        tier: 'pro',
        productSlugs: [ 'kadence' ],
        expires: 'December 31, 2026',
        isExpired: false,
        renewUrl: 'https://kadencewp.com/renew/',
    },

    // Expired single-product key
    {
        key: 'TEC-EXPIRED-2024-001',
        type: 'unified',
        tier: 'starter',
        productSlugs: [ 'the-events-calendar' ],
        expires: 'March 1, 2025',
        isExpired: true,
        renewUrl: 'https://evnt.is/renew',
    },
];

/** Look up a license by key. Returns undefined if not found (invalid key). */
export function findLicense( key: string ): License | undefined {
    return MOCK_LICENSES.find( ( l ) => l.key === key );
}
