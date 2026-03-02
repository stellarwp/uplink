<?php

declare(strict_types=1);

namespace StellarWP\Uplink\Features\API;

/**
 * Fixture feature catalog for development and testing.
 *
 * Mirrors the feature data defined in resources/js/data/products.ts.
 * Use the static factory methods to obtain pre-shaped variations,
 * e.g. only one product group, or with certain features unavailable.
 *
 * @since 3.0.0
 */
class Fixture
{

    /**
     * Returns the complete feature catalog with all features available.
     *
     * @since 3.0.0
     *
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        return self::catalog();
    }

    /**
     * Returns only the features belonging to the given product group.
     *
     * @since 3.0.0
     *
     * @param string $group The product group slug (e.g. 'givewp', 'learndash').
     *
     * @return array<int, array<string, mixed>>
     */
    public static function for_group(string $group): array
    {
        return array_values(
            array_filter(
                self::catalog(),
                static fn(array $f): bool => $f['group'] === $group
            )
        );
    }

    /**
     * Returns the full catalog with specific feature slugs marked as unavailable.
     *
     * @since 3.0.0
     *
     * @param string ...$slugs Feature slugs to mark as unavailable.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function with_unavailable(string ...$slugs): array
    {
        return array_map(
            static function (array $f) use ($slugs): array {
                if (in_array($f['slug'], $slugs, true)) {
                    $f['is_available'] = false;
                }
                return $f;
            },
            self::catalog()
        );
    }

    /**
     * Returns the full catalog with every feature marked as unavailable.
     *
     * @since 3.0.0
     *
     * @return array<int, array<string, mixed>>
     */
    public static function all_unavailable(): array
    {
        return array_map(
            static function (array $f): array {
                $f['is_available'] = false;
                return $f;
            },
            self::catalog()
        );
    }

    /**
     * Builds a single feature entry.
     *
     * All catalog entries are constructed through this method so that adding,
     * removing, or renaming a field only requires a change here.
     *
     * @since 3.0.0
     *
     * @param string $slug              The feature slug.
     * @param string $group             The product group slug.
     * @param string $tier              The required tier ('starter', 'pro', 'agency').
     * @param string $name              The display name.
     * @param string $description       Short description.
     * @param string $type              Feature type ('built_in', 'zip').
     * @param bool   $is_available      Whether the feature is available on this site.
     * @param string $documentation_url URL to documentation or learn-more page.
     *
     * @return array<string, mixed>
     */
    private static function entry(
        string $slug,
        string $group,
        string $tier,
        string $name,
        string $description,
        string $type = 'built_in',
        bool $is_available = true,
        string $documentation_url = ''
    ): array {
        return [
            'slug'              => $slug,
            'group'             => $group,
            'tier'              => $tier,
            'name'              => $name,
            'description'       => $description,
            'type'              => $type,
            'is_available'      => $is_available,
            'documentation_url' => $documentation_url,
        ];
    }

    /**
     * The raw feature catalog.
     *
     * Each entry maps directly to a Feature subclass via Client::$type_map.
     * Field names match the keys expected by Built_In::from_array() and Zip::from_array().
     *
     * @since 3.0.0
     *
     * @return array<int, array<string, mixed>>
     */
    private static function catalog(): array
    {
        return [

            // ── GiveWP ────────────────────────────────────────────────────────────

            // Starter
            self::entry(
                'givewp-donation-forms',
                'givewp',
                'starter',
                'Donation Forms',
                'Create unlimited donation forms',
            ),
            self::entry(
                'givewp-donor-management',
                'givewp',
                'starter',
                'Donor Management',
                'View and manage your donors',
            ),
            self::entry(
                'givewp-email-notifications',
                'givewp',
                'starter',
                'Email Notifications',
                'Automated donor and admin emails',
            ),
            self::entry(
                'givewp-reports-analytics',
                'givewp',
                'starter',
                'Reports & Analytics',
                'Donation reports and insights',
            ),
            self::entry(
                'givewp-stripe-gateway',
                'givewp',
                'starter',
                'Stripe Gateway',
                'Accept payments via Stripe',
            ),
            self::entry(
                'givewp-paypal-standard',
                'givewp',
                'starter',
                'PayPal Standard',
                'Accept payments via PayPal',
            ),

            // Pro
            self::entry(
                'givewp-recurring-donations',
                'givewp',
                'pro',
                'Recurring Donations',
                'Monthly and annual subscriptions',
            ),
            self::entry(
                'givewp-fee-recovery',
                'givewp',
                'pro',
                'Fee Recovery',
                'Let donors cover processing fees',
            ),
            self::entry(
                'givewp-stripe-checkout',
                'givewp',
                'pro',
                'Stripe Checkout',
                'Hosted Stripe checkout experience',
            ),
            self::entry(
                'givewp-pdf-receipts',
                'givewp',
                'pro',
                'PDF Receipts',
                'Downloadable donation receipts',
            ),
            self::entry(
                'givewp-import-export',
                'givewp',
                'pro',
                'Import & Export',
                'Bulk donor and donation import/export',
            ),
            self::entry(
                'givewp-tributes-dedications',
                'givewp',
                'pro',
                'Tributes & Dedications',
                'Honor someone with a donation',
            ),
            self::entry(
                'givewp-form-field-manager',
                'givewp',
                'pro',
                'Form Field Manager',
                'Custom fields on donation forms',
            ),
            self::entry(
                'givewp-donation-upsells', // cspell:ignore Upsells
                'givewp',
                'pro',
                'Donation Upsells',
                'Suggest recurring at checkout',
            ),

            // Agency
            self::entry(
                'givewp-peer-to-peer',
                'givewp',
                'agency',
                'Peer-to-Peer Fundraising',
                'Crowdfunding and team pages',
            ),
            self::entry(
                'givewp-virtual-terminal',
                'givewp',
                'agency',
                'Virtual Terminal',
                'Accept donations over the phone',
            ),
            self::entry(
                'givewp-funds-designations',
                'givewp',
                'agency',
                'Funds & Designations',
                'Restrict donations to specific funds',
            ),
            self::entry(
                'givewp-unlimited-sites',
                'givewp',
                'agency',
                'Unlimited Sites',
                'Use on any number of WordPress sites',
            ),

            // ── The Events Calendar ───────────────────────────────────────────────

            // Starter
            self::entry(
                'tec-event-calendar-view',
                'the-events-calendar',
                'starter',
                'Event Calendar View',
                'Monthly calendar grid display',
            ),
            self::entry(
                'tec-list-view',
                'the-events-calendar',
                'starter',
                'List View',
                'Upcoming events in list format',
            ),
            self::entry(
                'tec-event-search',
                'the-events-calendar',
                'starter',
                'Event Search',
                'Search and filter events',
            ),
            self::entry(
                'tec-google-maps',
                'the-events-calendar',
                'starter',
                'Google Maps Integration',
                'Show venue on a map',
            ),
            self::entry(
                'tec-organizer-profiles',
                'the-events-calendar',
                'starter',
                'Organizer Profiles',
                'Dedicated organizer pages',
            ),

            // Pro
            self::entry(
                'tec-recurring-events',
                'the-events-calendar',
                'pro',
                'Recurring Events',
                'Weekly, monthly, custom recurrence',
            ),
            self::entry(
                'tec-event-tickets',
                'the-events-calendar',
                'pro',
                'Event Tickets',
                'Free and paid ticketing',
            ),
            self::entry(
                'tec-rsvp',
                'the-events-calendar',
                'pro',
                'RSVP',
                'Attendee RSVP management',
            ),
            self::entry(
                'tec-week-day-views',
                'the-events-calendar',
                'pro',
                'Week & Day Views',
                'Additional calendar view modes',
            ),
            self::entry(
                'tec-photo-view',
                'the-events-calendar',
                'pro',
                'Photo View',
                'Image-focused event layout',
            ),
            self::entry(
                'tec-map-view',
                'the-events-calendar',
                'pro',
                'Map View',
                'Geospatial event map', // cspell:ignore Geospatial
            ),
            self::entry(
                'tec-ical-export',
                'the-events-calendar',
                'pro',
                'iCal Export',
                'Export events to calendar apps',
            ),

            // Agency
            self::entry(
                'tec-event-aggregator',
                'the-events-calendar',
                'agency',
                'Event Aggregator',
                'Import events from Meetup, Eventbrite',
            ),
            self::entry(
                'tec-filter-bar',
                'the-events-calendar',
                'agency',
                'Filter Bar',
                'Advanced event filtering UI',
            ),
            self::entry(
                'tec-community-events',
                'the-events-calendar',
                'agency',
                'Community Events',
                'Let visitors submit events',
            ),
            self::entry(
                'tec-unlimited-sites',
                'the-events-calendar',
                'agency',
                'Unlimited Sites',
                'Use on any number of WordPress sites',
            ),

            // ── LearnDash ─────────────────────────────────────────────────────────

            // Starter
            self::entry(
                'learndash-course-builder',
                'learndash',
                'starter',
                'Course Builder',
                'Drag-and-drop course creation',
            ),
            self::entry(
                'learndash-quizzes-assignments',
                'learndash',
                'starter',
                'Quizzes & Assignments',
                'Assessments with auto-grading',
            ),
            self::entry(
                'learndash-certificates',
                'learndash',
                'starter',
                'Certificates',
                'Customizable completion certificates',
            ),
            self::entry(
                'learndash-course-access-expiry',
                'learndash',
                'starter',
                'Course Access Expiry',
                'Time-limited course enrollment',
            ),
            self::entry(
                'learndash-drip-content',
                'learndash',
                'starter',
                'Drip Content',
                'Schedule lesson availability',
            ),

            // Pro
            self::entry(
                'learndash-groups-teams',
                'learndash',
                'pro',
                'Groups & Teams',
                'Group enrollments and team management',
            ),
            self::entry(
                'learndash-course-points',
                'learndash',
                'pro',
                'Course Points',
                'Gamification points system',
            ),
            self::entry(
                'learndash-woocommerce',
                'learndash',
                'pro',
                'WooCommerce Integration',
                'Sell courses via WooCommerce',
            ),
            self::entry(
                'learndash-stripe',
                'learndash',
                'pro',
                'Stripe Integration',
                'Built-in course payments',
            ),
            self::entry(
                'learndash-course-grid',
                'learndash',
                'pro',
                'Course Grid',
                'Visual course catalog layout',
            ),
            self::entry(
                'learndash-buddypress',
                'learndash',
                'pro',
                'BuddyPress Integration',
                'Social learning community',
            ),
            self::entry(
                'learndash-video-progression',
                'learndash',
                'pro',
                'Video Progression',
                'Require video completion to advance',
            ),

            // Agency
            self::entry(
                'learndash-propanel', // cspell:ignore ProPanel
                'learndash',
                'agency',
                'ProPanel',
                'Advanced reporting and analytics',
            ),
            self::entry(
                'learndash-tin-canny-reporting',
                'learndash',
                'agency',
                'Tin Canny Reporting',
                'SCORM/xAPI tracking', // cspell:ignore SCORM xAPI
            ),
            self::entry(
                'learndash-assignment-grading',
                'learndash',
                'agency',
                'Assignment Grading',
                'Manual assignment review workflow',
            ),
            self::entry(
                'learndash-unlimited-sites',
                'learndash',
                'agency',
                'Unlimited Sites',
                'Use on any number of WordPress sites',
            ),

            // ── Kadence ───────────────────────────────────────────────────────────

            // Starter
            self::entry(
                'kadence-theme',
                'kadence',
                'starter',
                'Kadence Theme',
                'Lightweight, fast WordPress theme',
            ),
            self::entry(
                'kadence-row-layout-block',
                'kadence',
                'starter',
                'Row Layout Block',
                'Advanced column and section layouts',
            ),
            self::entry(
                'kadence-advanced-button-block',
                'kadence',
                'starter',
                'Advanced Button Block',
                'Button groups with icons and styles',
            ),
            self::entry(
                'kadence-icon-block',
                'kadence',
                'starter',
                'Icon Block',
                'SVG icon library',
            ),
            self::entry(
                'kadence-info-box-block',
                'kadence',
                'starter',
                'Info Box Block',
                'Icon + title + text cards',
            ),

            // Pro
            self::entry(
                'kadence-advanced-header',
                'kadence',
                'pro',
                'Advanced Header Builder',
                'Drag-and-drop header with sticky behavior',
            ),
            self::entry(
                'kadence-mega-menu',
                'kadence',
                'pro',
                'Mega Menu',
                'Multi-column navigation menus',
            ),
            self::entry(
                'kadence-animations-effects',
                'kadence',
                'pro',
                'Animations & Effects',
                'Scroll and entrance animations',
            ),
            self::entry(
                'kadence-custom-fonts',
                'kadence',
                'pro',
                'Custom Fonts',
                'Upload and use any web font',
            ),
            self::entry(
                'kadence-conditional-headers',
                'kadence',
                'pro',
                'Conditional Headers/Footers',
                'Different headers per page type',
            ),
            self::entry(
                'kadence-modal-block',
                'kadence',
                'pro',
                'Modal Block',
                'Popup and lightbox content',
            ),
            self::entry(
                'kadence-form-block',
                'kadence',
                'pro',
                'Form Block',
                'Advanced forms with conditional logic',
            ),
            self::entry(
                'kadence-woocommerce-blocks',
                'kadence',
                'pro',
                'WooCommerce Blocks',
                'Styled shop, cart, and product blocks',
            ),

            // Agency
            self::entry(
                'kadence-starter-templates',
                'kadence',
                'agency',
                'Starter Templates',
                '200+ professionally designed templates',
            ),
            self::entry(
                'kadence-white-label',
                'kadence',
                'agency',
                'White Label',
                'Rebrand Kadence for your clients',
            ),
            self::entry(
                'kadence-user-role-control',
                'kadence',
                'agency',
                'User Role Control',
                'Per-role block restrictions',
            ),
            self::entry(
                'kadence-unlimited-sites',
                'kadence',
                'agency',
                'Unlimited Sites',
                'Use on any number of WordPress sites',
            ),

        ];
    }
}
