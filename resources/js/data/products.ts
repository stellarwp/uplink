/**
 * Product catalog data.
 *
 * Product metadata. Tier definitions and feature lists come from the
 * stellarwp/uplink/v1/catalog and stellarwp/uplink/v1/features REST
 * endpoints — not stored here.
 *
 * @package StellarWP\Uplink
 */
import type { Product } from '@/types/api';

export const PRODUCTS: Product[] = [
    {
        slug: 'give',
        name: 'GiveWP',
        tagline: 'Donation forms and fundraising for WordPress',
    },
    {
        slug: 'the-events-calendar',
        name: 'The Events Calendar',
        tagline: 'Powerful event management for WordPress',
    },
    {
        slug: 'learndash',
        name: 'LearnDash',
        tagline: 'World-class LMS for online courses',
    },
    {
        slug: 'kadence',
        name: 'Kadence',
        tagline: 'Page builder and theme toolkit for WordPress',
    },
];

/** Lookup a product by slug */
export function getProduct( slug: string ): Product | undefined {
    return PRODUCTS.find( ( p ) => p.slug === slug );
}
