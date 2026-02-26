/**
 * Product catalog data.
 *
 * 4 products × 3 tiers × features.
 * This replaces brands.ts + mock-features.json for the new data model.
 *
 * @package StellarWP\Uplink
 */
import type { Product } from '@/types/api';

export const PRODUCTS: Product[] = [
    {
        slug: 'givewp',
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
        features: [
            // Starter
            { id: 101, name: 'Donation Forms', description: 'Create unlimited donation forms', requiredTier: 'starter', category: 'Core' },
            { id: 102, name: 'Donor Management', description: 'View and manage your donors', requiredTier: 'starter', category: 'Core' },
            { id: 103, name: 'Email Notifications', description: 'Automated donor and admin emails', requiredTier: 'starter', category: 'Core' },
            { id: 104, name: 'Reports & Analytics', description: 'Donation reports and insights', requiredTier: 'starter', category: 'Core' },
            { id: 105, name: 'Stripe Gateway', description: 'Accept payments via Stripe', requiredTier: 'starter', category: 'Payments' },
            { id: 106, name: 'PayPal Standard', description: 'Accept payments via PayPal', requiredTier: 'starter', category: 'Payments' },
            // Pro
            { id: 107, name: 'Recurring Donations', description: 'Monthly and annual subscriptions', requiredTier: 'pro', category: 'Fundraising' },
            { id: 108, name: 'Fee Recovery', description: 'Let donors cover processing fees', requiredTier: 'pro', category: 'Payments' },
            { id: 109, name: 'Stripe Checkout', description: 'Hosted Stripe checkout experience', requiredTier: 'pro', category: 'Payments' },
            { id: 110, name: 'PDF Receipts', description: 'Downloadable donation receipts', requiredTier: 'pro', category: 'Donors' },
            { id: 111, name: 'Import & Export', description: 'Bulk donor and donation import/export', requiredTier: 'pro', category: 'Core' },
            { id: 112, name: 'Tributes & Dedications', description: 'Honor someone with a donation', requiredTier: 'pro', category: 'Fundraising' },
            { id: 113, name: 'Form Field Manager', description: 'Custom fields on donation forms', requiredTier: 'pro', category: 'Forms' },
            { id: 114, name: 'Donation Upsells', description: 'Suggest recurring at checkout', requiredTier: 'pro', category: 'Fundraising' }, // cspell:ignore Upsells
            // Agency
            { id: 115, name: 'Peer-to-Peer Fundraising', description: 'Crowdfunding and team pages', requiredTier: 'agency', category: 'Fundraising' },
            { id: 116, name: 'Virtual Terminal', description: 'Accept donations over the phone', requiredTier: 'agency', category: 'Payments' },
            { id: 117, name: 'Funds & Designations', description: 'Restrict donations to specific funds', requiredTier: 'agency', category: 'Fundraising' },
            { id: 118, name: 'Unlimited Sites', description: 'Use on any number of WordPress sites', requiredTier: 'agency', category: 'Licensing' },
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
        features: [
            // Starter
            { id: 201, name: 'Event Calendar View', description: 'Monthly calendar grid display', requiredTier: 'starter', category: 'Views' },
            { id: 202, name: 'List View', description: 'Upcoming events in list format', requiredTier: 'starter', category: 'Views' },
            { id: 203, name: 'Event Search', description: 'Search and filter events', requiredTier: 'starter', category: 'Core' },
            { id: 204, name: 'Google Maps Integration', description: 'Show venue on a map', requiredTier: 'starter', category: 'Venues' },
            { id: 205, name: 'Organizer Profiles', description: 'Dedicated organizer pages', requiredTier: 'starter', category: 'Organizers' },
            // Pro
            { id: 206, name: 'Recurring Events', description: 'Weekly, monthly, custom recurrence', requiredTier: 'pro', category: 'Core' },
            { id: 207, name: 'Event Tickets', description: 'Free and paid ticketing', requiredTier: 'pro', category: 'Ticketing' },
            { id: 208, name: 'RSVP', description: 'Attendee RSVP management', requiredTier: 'pro', category: 'Ticketing' },
            { id: 209, name: 'Week & Day Views', description: 'Additional calendar view modes', requiredTier: 'pro', category: 'Views' },
            { id: 210, name: 'Photo View', description: 'Image-focused event layout', requiredTier: 'pro', category: 'Views' },
            { id: 211, name: 'Map View', description: 'Geospatial event map', requiredTier: 'pro', category: 'Views' }, // cspell:ignore Geospatial
            { id: 212, name: 'iCal Export', description: 'Export events to calendar apps', requiredTier: 'pro', category: 'Core' },
            // Agency
            { id: 213, name: 'Event Aggregator', description: 'Import events from Meetup, Eventbrite', requiredTier: 'agency', category: 'Imports' },
            { id: 214, name: 'Filter Bar', description: 'Advanced event filtering UI', requiredTier: 'agency', category: 'Views' },
            { id: 215, name: 'Community Events', description: 'Let visitors submit events', requiredTier: 'agency', category: 'Community' },
            { id: 216, name: 'Unlimited Sites', description: 'Use on any number of WordPress sites', requiredTier: 'agency', category: 'Licensing' },
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
        features: [
            // Starter
            { id: 301, name: 'Course Builder', description: 'Drag-and-drop course creation', requiredTier: 'starter', category: 'Courses' },
            { id: 302, name: 'Quizzes & Assignments', description: 'Assessments with auto-grading', requiredTier: 'starter', category: 'Assessments' },
            { id: 303, name: 'Certificates', description: 'Customizable completion certificates', requiredTier: 'starter', category: 'Gamification' },
            { id: 304, name: 'Course Access Expiry', description: 'Time-limited course enrollment', requiredTier: 'starter', category: 'Courses' },
            { id: 305, name: 'Drip Content', description: 'Schedule lesson availability', requiredTier: 'starter', category: 'Courses' },
            // Pro
            { id: 306, name: 'Groups & Teams', description: 'Group enrollments and team management', requiredTier: 'pro', category: 'Users' },
            { id: 307, name: 'Course Points', description: 'Gamification points system', requiredTier: 'pro', category: 'Gamification' },
            { id: 308, name: 'WooCommerce Integration', description: 'Sell courses via WooCommerce', requiredTier: 'pro', category: 'eCommerce' },
            { id: 309, name: 'Stripe Integration', description: 'Built-in course payments', requiredTier: 'pro', category: 'eCommerce' },
            { id: 310, name: 'Course Grid', description: 'Visual course catalog layout', requiredTier: 'pro', category: 'Courses' },
            { id: 311, name: 'BuddyPress Integration', description: 'Social learning community', requiredTier: 'pro', category: 'Community' },
            { id: 312, name: 'Video Progression', description: 'Require video completion to advance', requiredTier: 'pro', category: 'Media' },
            // Agency
            { id: 313, name: 'ProPanel', description: 'Advanced reporting and analytics', requiredTier: 'agency', category: 'Analytics' },
            { id: 314, name: 'Tin Canny Reporting', description: 'SCORM/xAPI tracking', requiredTier: 'agency', category: 'Analytics' }, // cspell:ignore SCORM
            { id: 315, name: 'Assignment Grading', description: 'Manual assignment review workflow', requiredTier: 'agency', category: 'Assessments' },
            { id: 316, name: 'Unlimited Sites', description: 'Use on any number of WordPress sites', requiredTier: 'agency', category: 'Licensing' },
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
        features: [
            // Starter
            { id: 401, name: 'Kadence Theme', description: 'Lightweight, fast WordPress theme', requiredTier: 'starter', category: 'Theme' },
            { id: 402, name: 'Row Layout Block', description: 'Advanced column and section layouts', requiredTier: 'starter', category: 'Blocks' },
            { id: 403, name: 'Advanced Button Block', description: 'Button groups with icons and styles', requiredTier: 'starter', category: 'Blocks' },
            { id: 404, name: 'Icon Block', description: 'SVG icon library', requiredTier: 'starter', category: 'Blocks' },
            { id: 405, name: 'Info Box Block', description: 'Icon + title + text cards', requiredTier: 'starter', category: 'Blocks' },
            // Pro
            { id: 406, name: 'Advanced Header Builder', description: 'Drag-and-drop header with sticky behavior', requiredTier: 'pro', category: 'Theme' },
            { id: 407, name: 'Mega Menu', description: 'Multi-column navigation menus', requiredTier: 'pro', category: 'Theme' },
            { id: 408, name: 'Animations & Effects', description: 'Scroll and entrance animations', requiredTier: 'pro', category: 'Design' },
            { id: 409, name: 'Custom Fonts', description: 'Upload and use any web font', requiredTier: 'pro', category: 'Design' },
            { id: 410, name: 'Conditional Headers/Footers', description: 'Different headers per page type', requiredTier: 'pro', category: 'Theme' },
            { id: 411, name: 'Modal Block', description: 'Popup and lightbox content', requiredTier: 'pro', category: 'Blocks' },
            { id: 412, name: 'Form Block', description: 'Advanced forms with conditional logic', requiredTier: 'pro', category: 'Blocks' },
            { id: 413, name: 'WooCommerce Blocks', description: 'Styled shop, cart, and product blocks', requiredTier: 'pro', category: 'eCommerce' },
            // Agency
            { id: 414, name: 'Starter Templates', description: '200+ professionally designed templates', requiredTier: 'agency', category: 'Design' },
            { id: 415, name: 'White Label', description: 'Rebrand Kadence for your clients', requiredTier: 'agency', category: 'Agency' },
            { id: 416, name: 'User Role Control', description: 'Per-role block restrictions', requiredTier: 'agency', category: 'Agency' },
            { id: 417, name: 'Unlimited Sites', description: 'Use on any number of WordPress sites', requiredTier: 'agency', category: 'Licensing' },
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
