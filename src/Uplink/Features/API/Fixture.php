<?php

declare(strict_types=1);

namespace StellarWP\Uplink\Features\API;

use StellarWP\Uplink\Features\Types\Built_In;
use StellarWP\Uplink\Features\Types\Theme;
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

		if ( empty( $features ) ) {
			$self->features = $self->catalog();
		} elseif ( $merge ) {
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
	 * @param array<string, mixed> $attributes Feature attributes.
	 *
	 * @return array<string, mixed>
	 */
	public static function entry( array $attributes ): array {
		/** @var array<string, mixed> $attributes */
		$attributes = wp_parse_args(
			$attributes,
			[
				'is_available' => true,
				'type'         => 'zip',
			]
		);

		if ( $attributes['type'] === 'built_in' ) {
			$feature = Built_In::from_array( $attributes );

			return $feature->to_array();
		}

		if ( $attributes['type'] === 'zip' ) {
			$feature = Zip::from_array( $attributes );

			return $feature->to_array();
		}

		if ( $attributes['type'] === 'theme' ) {
			$feature = Theme::from_array( $attributes );

			return $feature->to_array();
		}

		return $attributes;
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
				[
					'slug'              => 'give-stripe-gateway',
					'group'             => 'give',
					'tier'              => 'starter',
					'name'              => 'Stripe Gateway',
					'description'       => 'Accept payments via Stripe',
					'type'              => 'zip',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
					'plugin_file'       => 'give-stripe-gateway.zip',
				]
			),
			self::entry(
				[
					'slug'              => 'give-paypal-standard',
					'group'             => 'give',
					'tier'              => 'starter',
					'name'              => 'PayPal Standard',
					'description'       => 'Accept payments via PayPal',
					'type'              => 'zip',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
					'plugin_file'       => 'give-paypal-standard.zip',
				]
			),

			// Pro.
			self::entry(
				[
					'slug'              => 'give-recurring-donations',
					'group'             => 'give',
					'tier'              => 'pro',
					'name'              => 'Recurring Donations',
					'description'       => 'Monthly and annual subscriptions',
					'type'              => 'zip',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
					'plugin_file'       => 'give-recurring-donations.zip',
				]
			),
			self::entry(
				[
					'slug'              => 'give-fee-recovery',
					'group'             => 'give',
					'tier'              => 'pro',
					'name'              => 'Fee Recovery',
					'description'       => 'Let donors cover processing fees',
					'type'              => 'zip',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
					'plugin_file'       => 'give-fee-recovery.zip',
				]
			),
			self::entry(
				[
					'slug'        => 'give-stripe-checkout',
					'group'       => 'give',
					'tier'        => 'pro',
					'name'        => 'Stripe Checkout',
					'description' => 'Hosted Stripe checkout experience',
				]
			),
			self::entry(
				[
					'slug'        => 'give-pdf-receipts',
					'group'       => 'give',
					'tier'        => 'pro',
					'name'        => 'PDF Receipts',
					'description' => 'Downloadable donation receipts',
				]
			),
			self::entry(
				[
					'slug'              => 'give-tributes-dedications',
					'group'             => 'give',
					'tier'              => 'pro',
					'name'              => 'Tributes & Dedications',
					'description'       => 'Honor someone with a donation',
					'type'              => 'zip',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
					'plugin_file'       => 'give-tributes-dedications.zip',
				]
			),
			self::entry(
				[
					'slug'              => 'give-form-field-manager',
					'group'             => 'give',
					'tier'              => 'pro',
					'name'              => 'Form Field Manager',
					'description'       => 'Custom fields on donation forms',
					'type'              => 'zip',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
					'plugin_file'       => 'give-form-field-manager.zip',
				]
			),

			// Agency.
			self::entry(
				[
					'slug'              => 'give-peer-to-peer',
					'group'             => 'give',
					'tier'              => 'agency',
					'name'              => 'Peer-to-Peer Fundraising',
					'description'       => 'Crowdfunding and team pages',
					'type'              => 'zip',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
					'plugin_file'       => 'give-peer-to-peer.zip',
				]
			),
			self::entry(
				[
					'slug'              => 'give-virtual-terminal',
					'group'             => 'give',
					'tier'              => 'agency',
					'name'              => 'Virtual Terminal',
					'description'       => 'Accept donations over the phone',
					'type'              => 'zip',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
					'plugin_file'       => 'give-virtual-terminal.zip',
				]
			),
			self::entry(
				[
					'slug'              => 'give-funds-designations',
					'group'             => 'give',
					'tier'              => 'agency',
					'name'              => 'Funds & Designations',
					'description'       => 'Restrict donations to specific funds',
					'type'              => 'zip',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
					'plugin_file'       => 'give-funds-designations.zip',
				]
			),

			// ── The Events Calendar ───────────────────────────────────────────────

			// Starter.
			self::entry(
				[
					'slug'              => 'tec-event-calendar-view',
					'group'             => 'the-events-calendar',
					'tier'              => 'starter',
					'name'              => 'Event Calendar View',
					'description'       => 'Monthly calendar grid display',
					'type'              => 'zip',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
					'plugin_file'       => 'tec-event-calendar-view.zip',
				]
			),
			self::entry(
				[
					'slug'              => 'tec-list-view',
					'group'             => 'the-events-calendar',
					'tier'              => 'starter',
					'name'              => 'List View',
					'description'       => 'Upcoming events in list format',
					'type'              => 'zip',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
					'plugin_file'       => 'tec-list-view.zip',
				]
			),
			self::entry(
				[
					'slug'              => 'tec-event-search',
					'group'             => 'the-events-calendar',
					'tier'              => 'starter',
					'name'              => 'Event Search',
					'description'       => 'Search and filter events',
					'type'              => 'zip',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
					'plugin_file'       => 'tec-event-search.zip',
				]
			),
			self::entry(
				[
					'slug'              => 'tec-google-maps',
					'group'             => 'the-events-calendar',
					'tier'              => 'starter',
					'name'              => 'Google Maps Integration',
					'description'       => 'Show venue on a map',
					'type'              => 'zip',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
					'plugin_file'       => 'tec-google-maps.zip',
				]
			),
			self::entry(
				[
					'slug'              => 'tec-organizer-profiles',
					'group'             => 'the-events-calendar',
					'tier'              => 'starter',
					'name'              => 'Organizer Profiles',
					'description'       => 'Dedicated organizer pages',
					'type'              => 'zip',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
					'plugin_file'       => 'tec-organizer-profiles.zip',
				]
			),

			// Pro.
			self::entry(
				[
					'slug'              => 'tec-recurring-events',
					'group'             => 'the-events-calendar',
					'tier'              => 'pro',
					'name'              => 'Recurring Events',
					'description'       => 'Weekly, monthly, custom recurrence',
					'type'              => 'zip',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
					'plugin_file'       => 'tec-recurring-events.zip',
				]
			),
			self::entry(
				[
					'slug'              => 'tec-event-tickets',
					'group'             => 'the-events-calendar',
					'tier'              => 'pro',
					'name'              => 'Event Tickets',
					'description'       => 'Free and paid ticketing',
					'type'              => 'zip',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
					'plugin_file'       => 'tec-event-tickets.zip',
				]
			),
			self::entry(
				[
					'slug'              => 'tec-rsvp',
					'group'             => 'the-events-calendar',
					'tier'              => 'pro',
					'name'              => 'RSVP',
					'description'       => 'Attendee RSVP management',
					'type'              => 'zip',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
					'plugin_file'       => 'tec-rsvp.zip',
				]
			),
			self::entry(
				[
					'slug'              => 'tec-week-day-views',
					'group'             => 'the-events-calendar',
					'tier'              => 'pro',
					'name'              => 'Week & Day Views',
					'description'       => 'Additional calendar view modes',
					'type'              => 'zip',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
					'plugin_file'       => 'tec-week-day-views.zip',
				]
			),
			self::entry(
				[
					'slug'              => 'tec-photo-view',
					'group'             => 'the-events-calendar',
					'tier'              => 'pro',
					'name'              => 'Photo View',
					'description'       => 'Image-focused event layout',
					'type'              => 'zip',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
					'plugin_file'       => 'tec-photo-view.zip',
				]
			),
			self::entry(
				[
					'slug'              => 'tec-map-view',
					'group'             => 'the-events-calendar',
					'tier'              => 'pro',
					'name'              => 'Map View',
					'description'       => 'Geospatial event map', // cspell:ignore Geospatial.
					'type'              => 'zip',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
					'plugin_file'       => 'tec-map-view.zip',
				]
			),
			self::entry(
				[
					'slug'              => 'tec-ical-export',
					'group'             => 'the-events-calendar',
					'tier'              => 'pro',
					'name'              => 'iCal Export',
					'description'       => 'Export events to calendar apps',
					'type'              => 'zip',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
					'plugin_file'       => 'tec-ical-export.zip',
				]
			),

			// Agency.
			self::entry(
				[
					'slug'              => 'tec-event-aggregator',
					'group'             => 'the-events-calendar',
					'tier'              => 'agency',
					'name'              => 'Event Aggregator',
					'description'       => 'Import events from Meetup, Eventbrite',
					'type'              => 'zip',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
					'plugin_file'       => 'tec-event-aggregator.zip',
				]
			),
			self::entry(
				[
					'slug'              => 'tec-filter-bar',
					'group'             => 'the-events-calendar',
					'tier'              => 'agency',
					'name'              => 'Filter Bar',
					'description'       => 'Advanced event filtering UI',
					'type'              => 'zip',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
					'plugin_file'       => 'tec-filter-bar.zip',
				]
			),
			self::entry(
				[
					'slug'              => 'tec-community-events',
					'group'             => 'the-events-calendar',
					'tier'              => 'agency',
					'name'              => 'Community Events',
					'description'       => 'Let visitors submit events',
					'type'              => 'zip',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
					'plugin_file'       => 'tec-community-events.zip',
				]
			),
			self::entry(
				[
					'slug'              => 'tec-unlimited-sites',
					'group'             => 'the-events-calendar',
					'tier'              => 'agency',
					'name'              => 'Unlimited Sites',
					'description'       => 'Use on any number of WordPress sites',
					'type'              => 'zip',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
					'plugin_file'       => 'tec-unlimited-sites.zip',
				]
			),

			// ── LearnDash ─────────────────────────────────────────────────────────

			// Starter.
			self::entry(
				[
					'slug'              => 'learndash-course-builder',
					'group'             => 'learndash',
					'tier'              => 'starter',
					'name'              => 'Course Builder',
					'description'       => 'Drag-and-drop course creation',
					'type'              => 'zip',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
					'plugin_file'       => 'learndash-course-builder.zip',
				]
			),
			self::entry(
				[
					'slug'              => 'learndash-quizzes-assignments',
					'group'             => 'learndash',
					'tier'              => 'starter',
					'name'              => 'Quizzes & Assignments',
					'description'       => 'Assessments with auto-grading',
					'type'              => 'zip',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
					'plugin_file'       => 'learndash-quizzes-assignments.zip',
				]
			),
			self::entry(
				[
					'slug'              => 'learndash-certificate-builder',
					'group'             => 'learndash',
					'tier'              => 'starter',
					'name'              => 'Learndash Certificate Builder',
					'description'       => 'LearnDash certificate builder allows you build certificates for your courses using the Gutenberg WordPress block editor.',
					'type'              => 'zip',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
					'plugin_file'       => 'learndash-certificate-builder/learndash-certificate-builder.php',
				]
			),
			self::entry(
				[
					'slug'              => 'learndash-course-access-expiry',
					'group'             => 'learndash',
					'tier'              => 'starter',
					'name'              => 'Course Access Expiry',
					'description'       => 'Time-limited course enrollment',
					'type'              => 'zip',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
					'plugin_file'       => 'learndash-course-access-expiry.zip',
				]
			),
			self::entry(
				[
					'slug'              => 'learndash-drip-content',
					'group'             => 'learndash',
					'tier'              => 'starter',
					'name'              => 'Drip Content',
					'description'       => 'Schedule lesson availability',
					'type'              => 'zip',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
					'plugin_file'       => 'learndash-drip-content.zip',
				]
			),

			// Pro.
			self::entry(
				[
					'slug'              => 'learndash-groups-teams',
					'group'             => 'learndash',
					'tier'              => 'pro',
					'name'              => 'Groups & Teams',
					'description'       => 'Group enrollments and team management',
					'type'              => 'zip',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
					'plugin_file'       => 'learndash-groups-teams.zip',
				]
			),
			self::entry(
				[
					'slug'              => 'learndash-course-points',
					'group'             => 'learndash',
					'tier'              => 'pro',
					'name'              => 'Course Points',
					'description'       => 'Gamification points system',
					'type'              => 'zip',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
					'plugin_file'       => 'learndash-course-points.zip',
				]
			),
			self::entry(
				[
					'slug'              => 'learndash-woocommerce',
					'group'             => 'learndash',
					'tier'              => 'pro',
					'name'              => 'WooCommerce Integration',
					'description'       => 'Sell courses via WooCommerce',
					'type'              => 'zip',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
					'plugin_file'       => 'learndash-woocommerce.zip',
				]
			),
			self::entry(
				[
					'slug'              => 'learndash-stripe',
					'group'             => 'learndash',
					'tier'              => 'pro',
					'name'              => 'Stripe Integration',
					'description'       => 'Built-in course payments',
					'type'              => 'zip',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
					'plugin_file'       => 'learndash-stripe.zip',
				]
			),
			self::entry(
				[
					'slug'              => 'learndash-course-grid',
					'group'             => 'learndash',
					'tier'              => 'pro',
					'name'              => 'Course Grid',
					'description'       => 'Visual course catalog layout',
					'type'              => 'zip',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
					'plugin_file'       => 'learndash-course-grid.zip',
				]
			),
			self::entry(
				[
					'slug'              => 'learndash-buddypress',
					'group'             => 'learndash',
					'tier'              => 'pro',
					'name'              => 'BuddyPress Integration',
					'description'       => 'Social learning community',
					'type'              => 'zip',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
					'plugin_file'       => 'learndash-buddypress.zip',
				]
			),
			self::entry(
				[
					'slug'              => 'learndash-video-progression',
					'group'             => 'learndash',
					'tier'              => 'pro',
					'name'              => 'Video Progression',
					'description'       => 'Require video completion to advance',
					'type'              => 'zip',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
					'plugin_file'       => 'learndash-video-progression.zip',
				]
			),

			// Agency.
			self::entry(
				[
					'slug'              => 'learndash-propanel', // cspell:ignore ProPanel.
					'group'             => 'learndash',
					'tier'              => 'agency',
					'name'              => 'ProPanel',
					'description'       => 'Advanced reporting and analytics',
					'type'              => 'zip',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
					'plugin_file'       => 'learndash-propanel.zip',
				]
			),
			self::entry(
				[
					'slug'              => 'learndash-tin-canny-reporting',
					'group'             => 'learndash',
					'tier'              => 'agency',
					'name'              => 'Tin Canny Reporting',
					'description'       => 'SCORM/xAPI tracking', // cspell:ignore SCORM xAPI.
					'type'              => 'zip',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
					'plugin_file'       => 'learndash-tin-canny-reporting.zip',
				]
			),
			self::entry(
				[
					'slug'              => 'learndash-assignment-grading',
					'group'             => 'learndash',
					'tier'              => 'agency',
					'name'              => 'Assignment Grading',
					'description'       => 'Manual assignment review workflow',
					'type'              => 'zip',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
					'plugin_file'       => 'learndash-assignment-grading.zip',
				]
			),
			self::entry(
				[
					'slug'              => 'learndash-unlimited-sites',
					'group'             => 'learndash',
					'tier'              => 'agency',
					'name'              => 'Unlimited Sites',
					'description'       => 'Use on any number of WordPress sites',
					'type'              => 'zip',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
					'plugin_file'       => 'learndash-unlimited-sites.zip',
				]
			),

			// ── Kadence ───────────────────────────────────────────────────────────

			// Starter.
			self::entry(
				[
					'slug'              => 'kadence-theme',
					'group'             => 'kadence',
					'tier'              => 'starter',
					'name'              => 'Kadence Theme',
					'description'       => 'Lightweight, fast WordPress theme',
					'type'              => 'theme',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
					'stylesheet'        => 'kadence',
					'authors'           => [ 'Starter Sites', 'Starter Templates', 'StellarWP' ],
				]
			),
			self::entry(
				[
					'slug'              => 'kadence-row-layout-block',
					'group'             => 'kadence',
					'tier'              => 'starter',
					'name'              => 'Row Layout Block',
					'description'       => 'Advanced column and section layouts',
					'type'              => 'built_in',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
				]
			),
			self::entry(
				[
					'slug'              => 'kadence-advanced-button-block',
					'group'             => 'kadence',
					'tier'              => 'starter',
					'name'              => 'Advanced Button Block',
					'description'       => 'Button groups with icons and styles',
					'type'              => 'built_in',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
				]
			),
			self::entry(
				[
					'slug'              => 'kadence-icon-block',
					'group'             => 'kadence',
					'tier'              => 'starter',
					'name'              => 'Icon Block',
					'description'       => 'SVG icon library',
					'type'              => 'built_in',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
				]
			),
			self::entry(
				[
					'slug'              => 'kadence-info-box-block',
					'group'             => 'kadence',
					'tier'              => 'starter',
					'name'              => 'Info Box Block',
					'description'       => 'Icon + title + text cards',
					'type'              => 'built_in',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
				]
			),

			// Pro.
			self::entry(
				[
					'slug'              => 'kadence-advanced-header',
					'group'             => 'kadence',
					'tier'              => 'pro',
					'name'              => 'Advanced Header Builder',
					'description'       => 'Drag-and-drop header with sticky behavior',
					'type'              => 'built_in',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
				]
			),
			self::entry(
				[
					'slug'              => 'kadence-mega-menu',
					'group'             => 'kadence',
					'tier'              => 'pro',
					'name'              => 'Mega Menu',
					'description'       => 'Multi-column navigation menus',
					'type'              => 'built_in',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
				]
			),
			self::entry(
				[
					'slug'              => 'kadence-animations-effects',
					'group'             => 'kadence',
					'tier'              => 'pro',
					'name'              => 'Animations & Effects',
					'description'       => 'Scroll and entrance animations',
					'type'              => 'built_in',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
				]
			),
			self::entry(
				[
					'slug'              => 'kadence-custom-fonts',
					'group'             => 'kadence',
					'tier'              => 'pro',
					'name'              => 'Custom Fonts',
					'description'       => 'Upload and use any web font',
					'type'              => 'built_in',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
				]
			),
			self::entry(
				[
					'slug'              => 'kadence-conditional-headers',
					'group'             => 'kadence',
					'tier'              => 'pro',
					'name'              => 'Conditional Headers/Footers',
					'description'       => 'Different headers per page type',
					'type'              => 'built_in',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
				]
			),
			self::entry(
				[
					'slug'              => 'kadence-modal-block',
					'group'             => 'kadence',
					'tier'              => 'pro',
					'name'              => 'Modal Block',
					'description'       => 'Popup and lightbox content',
					'type'              => 'built_in',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
				]
			),
			self::entry(
				[
					'slug'              => 'kadence-form-block',
					'group'             => 'kadence',
					'tier'              => 'pro',
					'name'              => 'Form Block',
					'description'       => 'Advanced forms with conditional logic',
					'type'              => 'built_in',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
				]
			),
			self::entry(
				[
					'slug'              => 'kadence-woocommerce-blocks',
					'group'             => 'kadence',
					'tier'              => 'pro',
					'name'              => 'WooCommerce Blocks',
					'description'       => 'Styled shop, cart, and product blocks',
					'type'              => 'built_in',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
				]
			),

			// Agency.
			self::entry(
				[
					'slug'              => 'kadence-starter-templates',
					'group'             => 'kadence',
					'tier'              => 'agency',
					'name'              => 'Starter Templates',
					'description'       => '200+ professionally designed templates',
					'type'              => 'zip',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
					'plugin_file'       => 'kadence-starter-templates.zip',
				]
			),
			self::entry(
				[
					'slug'              => 'kadence-white-label',
					'group'             => 'kadence',
					'tier'              => 'agency',
					'name'              => 'White Label',
					'description'       => 'Rebrand Kadence for your clients',
					'type'              => 'zip',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
					'plugin_file'       => 'kadence-white-label.zip',
				]
			),
			self::entry(
				[
					'slug'              => 'kadence-user-role-control',
					'group'             => 'kadence',
					'tier'              => 'agency',
					'name'              => 'User Role Control',
					'description'       => 'Per-role block restrictions',
					'type'              => 'zip',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
					'plugin_file'       => 'kadence-user-role-control.zip',
				]
			),
			self::entry(
				[
					'slug'              => 'kadence-unlimited-sites',
					'group'             => 'kadence',
					'tier'              => 'agency',
					'name'              => 'Unlimited Sites',
					'description'       => 'Use on any number of WordPress sites',
					'type'              => 'zip',
					'is_available'      => true,
					'documentation_url' => 'https://example.com/docs',
					'plugin_file'       => 'kadence-unlimited-sites.zip',
				]
			),

			// ── Zip Strategy Test Fixtures ────────────────────────────────────────

			self::entry(
				[
					'slug'              => 'built-in-feature',
					'group'             => 'Default',
					'tier'              => 'Tier 1',
					'name'              => 'Built-In Feature',
					'description'       => 'A feature built in to the plugin, toggled via a DB flag.',
					'type'              => 'built_in',
					'is_available'      => true,
					'documentation_url' => '',
				]
			),
			self::entry(
				[
					'slug'              => 'valid-zip-feature',
					'group'             => 'Default',
					'tier'              => 'Tier 1',
					'name'              => 'Valid Zip Feature',
					'description'       => 'A feature delivered as a standalone plugin ZIP.',
					'type'              => 'zip',
					'plugin_file'       => 'valid-zip-feature/valid-zip-feature.php',
					'is_available'      => true,
					'documentation_url' => '',
					'authors'           => [ 'StellarWP' ],
				]
			),
			self::entry(
				[
					'slug'              => 'wrong-author-zip-feature',
					'group'             => 'Default',
					'tier'              => 'Tier 1',
					'name'              => 'Wrong Author Zip Feature',
					'description'       => 'Tests PLUGIN_OWNERSHIP_MISMATCH — plugin Author is "Foreign Developer" but feature expects "StellarWP".', // cspell:ignore PLUGIN_OWNERSHIP_MISMATCH.
					'type'              => 'zip',
					'plugin_file'       => 'wrong-author-zip-feature/wrong-author-zip-feature.php',
					'is_available'      => true,
					'documentation_url' => '',
					'authors'           => [ 'StellarWP' ],
				]
			),
			self::entry(
				[
					'slug'              => 'fatal-zip-feature',
					'group'             => 'Default',
					'tier'              => 'Tier 1',
					'name'              => 'Fatal Zip Feature',
					'description'       => 'Tests ACTIVATION_FATAL — plugin throws RuntimeException on include.', // cspell:ignore ACTIVATION_FATAL.
					'type'              => 'zip',
					'plugin_file'       => 'fatal-zip-feature/fatal-zip-feature.php',
					'is_available'      => true,
					'documentation_url' => '',
					'authors'           => [ 'StellarWP' ],
				]
			),
			self::entry(
				[
					'slug'              => 'high-php-req-zip-feature',
					'group'             => 'Default',
					'tier'              => 'Tier 1',
					'name'              => 'High PHP Requirement Zip Feature',
					'description'       => 'Tests PREFLIGHT_REQUIREMENTS_NOT_MET — requires PHP 99.0.', // cspell:ignore PREFLIGHT_REQUIREMENTS_NOT_MET.
					'type'              => 'zip',
					'plugin_file'       => 'high-php-req-zip-feature/high-php-req-zip-feature.php',
					'is_available'      => true,
					'documentation_url' => '',
					'authors'           => [ 'StellarWP' ],
				]
			),
			self::entry(
				[
					'slug'              => 'high-wp-req-zip-feature',
					'group'             => 'Default',
					'tier'              => 'Tier 1',
					'name'              => 'High WP Requirement Zip Feature',
					'description'       => 'Tests PREFLIGHT_REQUIREMENTS_NOT_MET — requires WordPress 99.0.',
					'type'              => 'zip',
					'plugin_file'       => 'high-wp-req-zip-feature/high-wp-req-zip-feature.php',
					'is_available'      => true,
					'documentation_url' => '',
					'authors'           => [ 'StellarWP' ],
				]
			),
			self::entry(
				[
					'slug'              => 'deactivation-fatal-zip-feature',
					'group'             => 'Default',
					'tier'              => 'Tier 1',
					'name'              => 'Deactivation Fatal Zip Feature',
					'description'       => 'Tests DEACTIVATION_FAILED — plugin re-activates itself on deactivation.', // cspell:ignore DEACTIVATION_FAILED.
					'type'              => 'zip',
					'plugin_file'       => 'deactivation-fatal-zip-feature/deactivation-fatal-zip-feature.php',
					'is_available'      => true,
					'documentation_url' => '',
					'authors'           => [ 'StellarWP' ],
				]
			),
			self::entry(
				[
					'slug'              => 'mismatched-dir-zip-feature',
					'group'             => 'Default',
					'tier'              => 'Tier 1',
					'name'              => 'Mismatched Directory Zip Feature',
					'description'       => 'Tests PLUGIN_NOT_FOUND_AFTER_INSTALL — ZIP extracts to a different directory than plugin_file expects.', // cspell:ignore PLUGIN_NOT_FOUND_AFTER_INSTALL.
					'type'              => 'zip',
					'plugin_file'       => 'mismatched-dir-zip-feature/mismatched-dir-zip-feature.php',
					'is_available'      => true,
					'documentation_url' => '',
					'authors'           => [ 'StellarWP' ],
				]
			),
			self::entry(
				[
					'slug'              => 'syntax-error-zip-feature',
					'group'             => 'Default',
					'tier'              => 'Tier 1',
					'name'              => 'Syntax Error Zip Feature',
					'description'       => 'Tests ACTIVATION_FATAL — plugin contains a PHP ParseError.', // cspell:ignore ParseError.
					'type'              => 'zip',
					'plugin_file'       => 'syntax-error-zip-feature/syntax-error-zip-feature.php',
					'is_available'      => true,
					'documentation_url' => '',
					'authors'           => [ 'StellarWP' ],
				]
			),
			self::entry(
				[
					'slug'              => 'die-on-include-zip-feature',
					'group'             => 'Default',
					'tier'              => 'Tier 1',
					'name'              => 'Die On Include Zip Feature',
					'description'       => 'Tests uncatchable fatal — plugin calls die() on include.',
					'type'              => 'zip',
					'plugin_file'       => 'die-on-include-zip-feature/die-on-include-zip-feature.php',
					'is_available'      => true,
					'documentation_url' => '',
					'authors'           => [ 'StellarWP' ],
				]
			),
			self::entry(
				[
					'slug'              => 'activation-fatal-zip-feature',
					'group'             => 'Default',
					'tier'              => 'Tier 1',
					'name'              => 'Activation Fatal Zip Feature',
					'description'       => 'Tests ACTIVATION_FATAL — activation hook throws RuntimeException.',
					'type'              => 'zip',
					'plugin_file'       => 'activation-fatal-zip-feature/activation-fatal-zip-feature.php',
					'is_available'      => true,
					'documentation_url' => '',
					'authors'           => [ 'StellarWP' ],
				]
			),
			self::entry(
				[
					'slug'              => 'no-header-zip-feature',
					'group'             => 'Default',
					'tier'              => 'Tier 1',
					'name'              => 'No Header Zip Feature',
					'description'       => 'Tests PREFLIGHT_INVALID_PLUGIN — plugin file has no Plugin Name header.', // cspell:ignore PREFLIGHT_INVALID_PLUGIN.
					'type'              => 'zip',
					'plugin_file'       => 'no-header-zip-feature/no-header-zip-feature.php',
					'is_available'      => true,
					'documentation_url' => '',
					'authors'           => [ 'StellarWP' ],
				]
			),
			self::entry(
				[
					'slug'              => 'no-source-zip-feature',
					'group'             => 'Default',
					'tier'              => 'Tier 1',
					'name'              => 'No Source Zip Feature',
					'description'       => 'Tests PLUGINS_API_FAILED — feature exists in catalog but has no ZIP source directory.', // cspell:ignore PLUGINS_API_FAILED.
					'type'              => 'zip',
					'plugin_file'       => 'no-source-zip-feature/no-source-zip-feature.php',
					'is_available'      => true,
					'documentation_url' => '',
					'authors'           => [ 'StellarWP' ],
				]
			),
			self::entry(
				[
					'slug'              => 'unavailable-zip-feature',
					'group'             => 'Default',
					'tier'              => 'Tier 1',
					'name'              => 'Unavailable Zip Feature',
					'description'       => 'Tests REST validation — is_available is false, rejected before reaching the strategy.',
					'type'              => 'zip',
					'plugin_file'       => 'unavailable-zip-feature/unavailable-zip-feature.php',
					'is_available'      => false,
					'documentation_url' => '',
					'authors'           => [ 'StellarWP' ],
				]
			),

			// ── Theme Strategy Test Fixtures ────────────────────────────────────

			self::entry(
				[
					'slug'              => 'valid-theme-feature',
					'group'             => 'Default',
					'tier'              => 'Tier 1',
					'name'              => 'Valid Theme Feature',
					'description'       => 'A feature delivered as a standalone WordPress theme.',
					'type'              => 'theme',
					'stylesheet'        => 'valid-theme-feature',
					'is_available'      => true,
					'documentation_url' => '',
					'authors'           => [ 'StellarWP' ],
				]
			),
			self::entry(
				[
					'slug'              => 'wrong-author-theme-feature',
					'group'             => 'Default',
					'tier'              => 'Tier 1',
					'name'              => 'Wrong Author Theme Feature',
					'description'       => 'Tests THEME_OWNERSHIP_MISMATCH — theme Author is "Foreign Developer" but feature expects "StellarWP".', // cspell:ignore THEME_OWNERSHIP_MISMATCH.
					'type'              => 'theme',
					'stylesheet'        => 'wrong-author-theme-feature',
					'is_available'      => true,
					'documentation_url' => '',
					'authors'           => [ 'StellarWP' ],
				]
			),

		];
	}
}
