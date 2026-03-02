<?php

declare(strict_types=1);

namespace StellarWP\Uplink\Features\API;

use StellarWP\Uplink\Features\Types\Built_In;
use StellarWP\Uplink\Features\Types\Zip;

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
	 * Returns the feature data.
	 *
	 * @since 3.0.0
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get(): array {
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
	 * @param string $plugin_file       Path to the plugin file (only used for zip features).
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
		string $documentation_url = '',
		string $plugin_file = ''
	): array {
		$data = [
			'slug'              => $slug,
			'group'             => $group,
			'tier'              => $tier,
			'name'              => $name,
			'description'       => $description,
			'is_available'      => $is_available,
			'documentation_url' => $documentation_url,
		];

		if ( $type === 'built_in' ) {
			$feature = Built_In::from_array( $data );

			return $feature->to_array();
		}

		if ( $type === 'zip' ) {
			$data['plugin_file'] = $plugin_file;
			$feature             = Zip::from_array( $data );

			return $feature->to_array();
		}

		return $data;
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
				'zip',
				true,
				'https://example.com/docs',
				'give-stripe-gateway.zip',
			),
			self::entry(
				'give-paypal-standard',
				'give',
				'starter',
				'PayPal Standard',
				'Accept payments via PayPal',
				'zip',
				true,
				'https://example.com/docs',
				'give-paypal-standard.zip',
			),

			// Pro.
			self::entry(
				'give-recurring-donations',
				'give',
				'pro',
				'Recurring Donations',
				'Monthly and annual subscriptions',
				'zip',
				true,
				'https://example.com/docs',
				'give-recurring-donations.zip',
			),
			self::entry(
				'give-fee-recovery',
				'give',
				'pro',
				'Fee Recovery',
				'Let donors cover processing fees',
				'zip',
				true,
				'https://example.com/docs',
				'give-fee-recovery.zip',
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
				'zip',
				true,
				'https://example.com/docs',
				'give-tributes-dedications.zip',
			),
			self::entry(
				'give-form-field-manager',
				'give',
				'pro',
				'Form Field Manager',
				'Custom fields on donation forms',
				'zip',
				true,
				'https://example.com/docs',
				'give-form-field-manager.zip',
			),

			// Agency.
			self::entry(
				'give-peer-to-peer',
				'give',
				'agency',
				'Peer-to-Peer Fundraising',
				'Crowdfunding and team pages',
				'zip',
				true,
				'https://example.com/docs',
				'give-peer-to-peer.zip',
			),
			self::entry(
				'give-virtual-terminal',
				'give',
				'agency',
				'Virtual Terminal',
				'Accept donations over the phone',
				'zip',
				true,
				'https://example.com/docs',
				'give-virtual-terminal.zip',
			),
			self::entry(
				'give-funds-designations',
				'give',
				'agency',
				'Funds & Designations',
				'Restrict donations to specific funds',
				'zip',
				true,
				'https://example.com/docs',
				'give-funds-designations.zip',
			),

			// ── The Events Calendar ───────────────────────────────────────────────

			// Starter.
			self::entry(
				'tec-event-calendar-view',
				'the-events-calendar',
				'starter',
				'Event Calendar View',
				'Monthly calendar grid display',
				'zip',
				true,
				'https://example.com/docs',
				'tec-event-calendar-view.zip',
			),
			self::entry(
				'tec-list-view',
				'the-events-calendar',
				'starter',
				'List View',
				'Upcoming events in list format',
				'zip',
				true,
				'https://example.com/docs',
				'tec-list-view.zip',
			),
			self::entry(
				'tec-event-search',
				'the-events-calendar',
				'starter',
				'Event Search',
				'Search and filter events',
				'zip',
				true,
				'https://example.com/docs',
				'tec-event-search.zip',
			),
			self::entry(
				'tec-google-maps',
				'the-events-calendar',
				'starter',
				'Google Maps Integration',
				'Show venue on a map',
				'zip',
				true,
				'https://example.com/docs',
				'tec-google-maps.zip',
			),
			self::entry(
				'tec-organizer-profiles',
				'the-events-calendar',
				'starter',
				'Organizer Profiles',
				'Dedicated organizer pages',
				'zip',
				true,
				'https://example.com/docs',
				'tec-organizer-profiles.zip',
			),

			// Pro.
			self::entry(
				'tec-recurring-events',
				'the-events-calendar',
				'pro',
				'Recurring Events',
				'Weekly, monthly, custom recurrence',
				'zip',
				true,
				'https://example.com/docs',
				'tec-recurring-events.zip',
			),
			self::entry(
				'tec-event-tickets',
				'the-events-calendar',
				'pro',
				'Event Tickets',
				'Free and paid ticketing',
				'zip',
				true,
				'https://example.com/docs',
				'tec-event-tickets.zip',
			),
			self::entry(
				'tec-rsvp',
				'the-events-calendar',
				'pro',
				'RSVP',
				'Attendee RSVP management',
				'zip',
				true,
				'https://example.com/docs',
				'tec-rsvp.zip',
			),
			self::entry(
				'tec-week-day-views',
				'the-events-calendar',
				'pro',
				'Week & Day Views',
				'Additional calendar view modes',
				'zip',
				true,
				'https://example.com/docs',
				'tec-week-day-views.zip',
			),
			self::entry(
				'tec-photo-view',
				'the-events-calendar',
				'pro',
				'Photo View',
				'Image-focused event layout',
				'zip',
				true,
				'https://example.com/docs',
				'tec-photo-view.zip',
			),
			self::entry(
				'tec-map-view',
				'the-events-calendar',
				'pro',
				'Map View',
				'Geospatial event map', // cspell:ignore Geospatial.
				'zip',
				true,
				'https://example.com/docs',
				'tec-map-view.zip',
			),
			self::entry(
				'tec-ical-export',
				'the-events-calendar',
				'pro',
				'iCal Export',
				'Export events to calendar apps',
				'zip',
				true,
				'https://example.com/docs',
				'tec-ical-export.zip',
			),

			// Agency.
			self::entry(
				'tec-event-aggregator',
				'the-events-calendar',
				'agency',
				'Event Aggregator',
				'Import events from Meetup, Eventbrite',
				'zip',
				true,
				'https://example.com/docs',
				'tec-event-aggregator.zip',
			),
			self::entry(
				'tec-filter-bar',
				'the-events-calendar',
				'agency',
				'Filter Bar',
				'Advanced event filtering UI',
				'zip',
				true,
				'https://example.com/docs',
				'tec-filter-bar.zip',
			),
			self::entry(
				'tec-community-events',
				'the-events-calendar',
				'agency',
				'Community Events',
				'Let visitors submit events',
				'zip',
				true,
				'https://example.com/docs',
				'tec-community-events.zip',
			),
			self::entry(
				'tec-unlimited-sites',
				'the-events-calendar',
				'agency',
				'Unlimited Sites',
				'Use on any number of WordPress sites',
				'zip',
				true,
				'https://example.com/docs',
				'tec-unlimited-sites.zip',
			),

			// ── LearnDash ─────────────────────────────────────────────────────────

			// Starter.
			self::entry(
				'learndash-course-builder',
				'learndash',
				'starter',
				'Course Builder',
				'Drag-and-drop course creation',
				'zip',
				true,
				'https://example.com/docs',
				'learndash-course-builder.zip',
			),
			self::entry(
				'learndash-quizzes-assignments',
				'learndash',
				'starter',
				'Quizzes & Assignments',
				'Assessments with auto-grading',
				'zip',
				true,
				'https://example.com/docs',
				'learndash-quizzes-assignments.zip',
			),
			self::entry(
				'learndash-certificates',
				'learndash',
				'starter',
				'Certificates',
				'Customizable completion certificates',
				'zip',
				true,
				'https://example.com/docs',
				'learndash-certificates.zip',
			),
			self::entry(
				'learndash-course-access-expiry',
				'learndash',
				'starter',
				'Course Access Expiry',
				'Time-limited course enrollment',
				'zip',
				true,
				'https://example.com/docs',
				'learndash-course-access-expiry.zip',
			),
			self::entry(
				'learndash-drip-content',
				'learndash',
				'starter',
				'Drip Content',
				'Schedule lesson availability',
				'zip',
				true,
				'https://example.com/docs',
				'learndash-drip-content.zip',
			),

			// Pro.
			self::entry(
				'learndash-groups-teams',
				'learndash',
				'pro',
				'Groups & Teams',
				'Group enrollments and team management',
				'zip',
				true,
				'https://example.com/docs',
				'learndash-groups-teams.zip',
			),
			self::entry(
				'learndash-course-points',
				'learndash',
				'pro',
				'Course Points',
				'Gamification points system',
				'zip',
				true,
				'https://example.com/docs',
				'learndash-course-points.zip',
			),
			self::entry(
				'learndash-woocommerce',
				'learndash',
				'pro',
				'WooCommerce Integration',
				'Sell courses via WooCommerce',
				'zip',
				true,
				'https://example.com/docs',
				'learndash-woocommerce.zip',
			),
			self::entry(
				'learndash-stripe',
				'learndash',
				'pro',
				'Stripe Integration',
				'Built-in course payments',
				'zip',
				true,
				'https://example.com/docs',
				'learndash-stripe.zip',
			),
			self::entry(
				'learndash-course-grid',
				'learndash',
				'pro',
				'Course Grid',
				'Visual course catalog layout',
				'zip',
				true,
				'https://example.com/docs',
				'learndash-course-grid.zip',
			),
			self::entry(
				'learndash-buddypress',
				'learndash',
				'pro',
				'BuddyPress Integration',
				'Social learning community',
				'zip',
				true,
				'https://example.com/docs',
				'learndash-buddypress.zip',
			),
			self::entry(
				'learndash-video-progression',
				'learndash',
				'pro',
				'Video Progression',
				'Require video completion to advance',
				'zip',
				true,
				'https://example.com/docs',
				'learndash-video-progression.zip',
			),

			// Agency.
			self::entry(
				'learndash-propanel', // cspell:ignore ProPanel.
				'learndash',
				'agency',
				'ProPanel',
				'Advanced reporting and analytics',
				'zip',
				true,
				'https://example.com/docs',
				'learndash-propanel.zip',
			),
			self::entry(
				'learndash-tin-canny-reporting',
				'learndash',
				'agency',
				'Tin Canny Reporting',
				'SCORM/xAPI tracking', // cspell:ignore SCORM xAPI.
				'zip',
				true,
				'https://example.com/docs',
				'learndash-tin-canny-reporting.zip',
			),
			self::entry(
				'learndash-assignment-grading',
				'learndash',
				'agency',
				'Assignment Grading',
				'Manual assignment review workflow',
				'zip',
				true,
				'https://example.com/docs',
				'learndash-assignment-grading.zip',
			),
			self::entry(
				'learndash-unlimited-sites',
				'learndash',
				'agency',
				'Unlimited Sites',
				'Use on any number of WordPress sites',
				'zip',
				true,
				'https://example.com/docs',
				'learndash-unlimited-sites.zip',
			),

			// ── Kadence ───────────────────────────────────────────────────────────

			// Starter.
			self::entry(
				'kadence-theme',
				'kadence',
				'starter',
				'Kadence Theme',
				'Lightweight, fast WordPress theme',
				'zip',
				true,
				'https://example.com/docs',
				'kadence-theme.zip',
			),
			self::entry(
				'kadence-row-layout-block',
				'kadence',
				'starter',
				'Row Layout Block',
				'Advanced column and section layouts',
				'built_in',
				true,
				'https://example.com/docs',
			),
			self::entry(
				'kadence-advanced-button-block',
				'kadence',
				'starter',
				'Advanced Button Block',
				'Button groups with icons and styles',
				'built_in',
				true,
				'https://example.com/docs',
			),
			self::entry(
				'kadence-icon-block',
				'kadence',
				'starter',
				'Icon Block',
				'SVG icon library',
				'built_in',
				true,
				'https://example.com/docs',
			),
			self::entry(
				'kadence-info-box-block',
				'kadence',
				'starter',
				'Info Box Block',
				'Icon + title + text cards',
				'built_in',
				true,
				'https://example.com/docs',
			),

			// Pro.
			self::entry(
				'kadence-advanced-header',
				'kadence',
				'pro',
				'Advanced Header Builder',
				'Drag-and-drop header with sticky behavior',
				'built_in',
				true,
				'https://example.com/docs',
			),
			self::entry(
				'kadence-mega-menu',
				'kadence',
				'pro',
				'Mega Menu',
				'Multi-column navigation menus',
				'built_in',
				true,
				'https://example.com/docs',
			),
			self::entry(
				'kadence-animations-effects',
				'kadence',
				'pro',
				'Animations & Effects',
				'Scroll and entrance animations',
				'built_in',
				true,
				'https://example.com/docs',
			),
			self::entry(
				'kadence-custom-fonts',
				'kadence',
				'pro',
				'Custom Fonts',
				'Upload and use any web font',
				'built_in',
				true,
				'https://example.com/docs',
			),
			self::entry(
				'kadence-conditional-headers',
				'kadence',
				'pro',
				'Conditional Headers/Footers',
				'Different headers per page type',
				'built_in',
				true,
				'https://example.com/docs',
			),
			self::entry(
				'kadence-modal-block',
				'kadence',
				'pro',
				'Modal Block',
				'Popup and lightbox content',
				'built_in',
				true,
				'https://example.com/docs',
			),
			self::entry(
				'kadence-form-block',
				'kadence',
				'pro',
				'Form Block',
				'Advanced forms with conditional logic',
				'built_in',
				true,
				'https://example.com/docs',
			),
			self::entry(
				'kadence-woocommerce-blocks',
				'kadence',
				'pro',
				'WooCommerce Blocks',
				'Styled shop, cart, and product blocks',
				'built_in',
				true,
				'https://example.com/docs',
			),

			// Agency.
			self::entry(
				'kadence-starter-templates',
				'kadence',
				'agency',
				'Starter Templates',
				'200+ professionally designed templates',
				'zip',
				true,
				'https://example.com/docs',
				'kadence-starter-templates.zip',
			),
			self::entry(
				'kadence-white-label',
				'kadence',
				'agency',
				'White Label',
				'Rebrand Kadence for your clients',
				'zip',
				true,
				'https://example.com/docs',
				'kadence-white-label.zip',
			),
			self::entry(
				'kadence-user-role-control',
				'kadence',
				'agency',
				'User Role Control',
				'Per-role block restrictions',
				'zip',
				true,
				'https://example.com/docs',
				'kadence-user-role-control.zip',
			),
			self::entry(
				'kadence-unlimited-sites',
				'kadence',
				'agency',
				'Unlimited Sites',
				'Use on any number of WordPress sites',
				'zip',
				true,
				'https://example.com/docs',
				'kadence-unlimited-sites.zip',
			),

		];
	}
}
