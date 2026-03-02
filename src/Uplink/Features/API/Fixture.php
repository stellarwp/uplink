<?php

declare(strict_types=1);

namespace StellarWP\Uplink\Features\API;

/**
 * Fixture feature catalog for development and testing.
 *
 * @since 3.0.0
 */
class Fixture {


	/**
	 * @since 3.0.0
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $features = [];

	/**
	 * Creates a new Fixture instance.
	 *
	 * @since 3.0.0
	 *
	 * @param array<int, array<string, mixed>> $features Additional features to add to the catalog.
	 * @param bool                             $merge Whether to merge the features with the default catalog.
	 *
	 * @return self
	 */
	public static function create( array $features = [], bool $merge = false ): self {
		$self = new self();

		if ( $merge && ! empty( $features ) ) {
			$self->features = array_merge( $self->catalog(), $features );
		} else {
			$self->features = $features;
		}

		return $self;
	}

	/**
	 * Returns the complete feature catalog with all features available.
	 *
	 * @since 3.0.0
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function all(): array {
		return $this->features;
	}

	/**
	 * Returns the full catalog filtered by the given key and value.
	 *
	 * @since 3.0.0
	 *
	 * @param string $key The key to filter by.
	 * @param mixed  $value The value to filter by.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function filter_by( string $key, mixed $value ): array {
		return array_values(
			array_filter(
				$this->features,
				static fn( mixed $f ): bool => ( $f[ $key ] ?? null ) === $value
			)
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
	public static function entry(
		string $slug,
		string $group,
		string $tier,
		string $name,
		string $description = '',
		string $type = 'zip',
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
	public function catalog(): array {
		return [

			// ── give ────────────────────────────────────────────────────────────

			// give features.
			self::entry(
				'give-stripe-gateway',
				'give',
				'starter',
				'Stripe Gateway',
				'Accept payments via Stripe',
			),
			self::entry(
				'give-paypal-standard',
				'give',
				'starter',
				'PayPal Standard',
				'Accept payments via PayPal',
			),

			// Pro.
			self::entry(
				'give-recurring-donations',
				'give',
				'pro',
				'Recurring Donations',
				'Monthly and annual subscriptions',
			),
			self::entry(
				'give-fee-recovery',
				'give',
				'pro',
				'Fee Recovery',
				'Let donors cover processing fees',
			),
			self::entry(
				'give-stripe-checkout',
				'give',
				'pro',
				'Stripe Checkout',
				'Hosted Stripe checkout experience',
			),
			self::entry(
				'give-pdf-receipts',
				'give',
				'pro',
				'PDF Receipts',
				'Downloadable donation receipts',
			),
			self::entry(
				'give-tributes-dedications',
				'give',
				'pro',
				'Tributes & Dedications',
				'Honor someone with a donation',
			),
			self::entry(
				'give-form-field-manager',
				'give',
				'pro',
				'Form Field Manager',
				'Custom fields on donation forms',
			),

			// Agency.
			self::entry(
				'give-peer-to-peer',
				'give',
				'agency',
				'Peer-to-Peer Fundraising',
				'Crowdfunding and team pages',
			),
			self::entry(
				'give-virtual-terminal',
				'give',
				'agency',
				'Virtual Terminal',
				'Accept donations over the phone',
			),
			self::entry(
				'give-funds-designations',
				'give',
				'agency',
				'Funds & Designations',
				'Restrict donations to specific funds',
			),

			// ── The Events Calendar ───────────────────────────────────────────────

			// Starter.
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

			// Pro.
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
				'Geospatial event map', // cspell:ignore Geospatial.
			),
			self::entry(
				'tec-ical-export',
				'the-events-calendar',
				'pro',
				'iCal Export',
				'Export events to calendar apps',
			),

			// Agency.
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

			// Starter.
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

			// Pro.
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

			// Agency.
			self::entry(
				'learndash-propanel', // cspell:ignore ProPanel.
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
				'SCORM/xAPI tracking', // cspell:ignore SCORM xAPI.
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

			// Starter.
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

			// Pro.
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

			// Agency.
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
