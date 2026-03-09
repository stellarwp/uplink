/**
 * Product catalog data.
 *
 * Product metadata and tier definitions. Features come from the
 * stellarwp/uplink/v1/features REST endpoint — not stored here.
 *
 * @package StellarWP\Uplink
 */
import type { Product } from '@/types/api';

export const PRODUCTS: Product[] = [
    {
        slug: 'give',
        name: 'GiveWP',
        tagline: 'Donation forms and fundraising for WordPress',
        tiers: [
            {
                slug: 'starter',
                name: 'Starter',
                description: 'Core donation features for getting started',
                upgradeUrl: 'https://givewp.com/pricing/',
            },
            {
                slug: 'pro',
                name: 'Pro',
                description: 'Advanced payment gateways and recurring donations',
                upgradeUrl: 'https://givewp.com/pricing/',
            },
            {
                slug: 'agency',
                name: 'Agency',
                description: 'Unlimited sites, peer-to-peer fundraising, and more',
                upgradeUrl: 'https://givewp.com/pricing/',
            },
        ],
    },
    {
        slug: 'the-events-calendar',
        name: 'The Events Calendar',
        tagline: 'Powerful event management for WordPress',
        tiers: [
            {
                slug: 'starter',
                name: 'Starter',
                description: 'Core event calendar features',
                upgradeUrl: 'https://evnt.is/pricing',
            },
            {
                slug: 'pro',
                name: 'Pro',
                description: 'Recurring events, tickets, and more',
                upgradeUrl: 'https://evnt.is/pricing',
            },
            {
                slug: 'agency',
                name: 'Agency',
                description: 'Unlimited sites and premium add-ons',
                upgradeUrl: 'https://evnt.is/pricing',
            },
        ],
    },
    {
        slug: 'learndash',
        name: 'LearnDash',
        tagline: 'World-class LMS for online courses',
        tiers: [
            {
                slug: 'starter',
                name: 'Starter',
                description: 'Core LMS features for one site',
                upgradeUrl: 'https://learndash.com/pricing/',
            },
            {
                slug: 'pro',
                name: 'Pro',
                description: 'Advanced courses, groups, and reporting',
                upgradeUrl: 'https://learndash.com/pricing/',
            },
            {
                slug: 'agency',
                name: 'Agency',
                description: 'Unlimited sites and ProPanel analytics',
                upgradeUrl: 'https://learndash.com/pricing/',
            },
        ],
    },
    {
        slug: 'kadence',
        name: 'Kadence',
        tagline: 'Page builder and theme toolkit for WordPress',
        tiers: [
            {
                slug: 'starter',
                name: 'Starter',
                description: 'Core Kadence blocks and theme',
                upgradeUrl: 'https://kadencewp.com/pricing/',
            },
            {
                slug: 'pro',
                name: 'Pro',
                description: 'Advanced blocks, animations, and global styles',
                upgradeUrl: 'https://kadencewp.com/pricing/',
            },
            {
                slug: 'agency',
                name: 'Agency',
                description: 'Unlimited sites, white-label, and starter templates',
                upgradeUrl: 'https://kadencewp.com/pricing/',
            },
        ],
    },
];

/** Lookup a product by slug */
export function getProduct( slug: string ): Product | undefined {
    return PRODUCTS.find( ( p ) => p.slug === slug );
}

/** Get display name for a tier */
export function getTierName( slug: string ): string {
    for ( const product of PRODUCTS ) {
        const tier = product.tiers.find( ( t ) => t.slug === slug );
        if ( tier ) return tier.name;
    }
    return slug;
}
