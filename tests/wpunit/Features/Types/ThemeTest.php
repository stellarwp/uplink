<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Features\Types;

use StellarWP\Uplink\Features\Contracts\Installable;
use StellarWP\Uplink\Features\Types\Theme;
use StellarWP\Uplink\Tests\UplinkTestCase;

final class ThemeTest extends UplinkTestCase {

	/**
	 * Standard test values.
	 */
	private const SLUG       = 'kadence';
	private const GROUP      = 'Kadence';
	private const TIER       = 'Tier 1';
	private const NAME       = 'Kadence Theme';
	private const DESCRIPTION = 'Lightweight, fast WordPress theme.';
	private const STYLESHEET = 'kadence';

	/**
	 * Create a Theme feature with configurable values.
	 *
	 * @param string   $slug        Feature slug.
	 * @param string   $name        Display name.
	 * @param string   $description Description.
	 * @param string   $stylesheet  Theme stylesheet.
	 * @param string[] $authors     Expected theme authors.
	 *
	 * @return Theme
	 */
	private function make_feature(
		string $slug = self::SLUG,
		string $name = self::NAME,
		string $description = self::DESCRIPTION,
		string $stylesheet = self::STYLESHEET,
		array $authors = [ 'StellarWP' ]
	): Theme {
		return new Theme(
			[
				'slug'         => $slug,
				'group'        => self::GROUP,
				'tier'         => self::TIER,
				'name'         => $name,
				'description'  => $description,
				'stylesheet'   => $stylesheet,
				'is_available' => true,
				'authors'      => $authors,
			]
		);
	}

	// -------------------------------------------------------------------------
	// from_array() tests
	// -------------------------------------------------------------------------

	/**
	 * Tests a Theme feature can be hydrated from an associative array.
	 *
	 * @return void
	 */
	public function test_it_creates_from_array(): void {
		$feature = Theme::from_array(
			[
				'slug'              => 'test-theme',
				'group'             => 'Kadence',
				'tier'              => 'Tier 2',
				'name'              => 'Test Theme',
				'description'       => 'Test theme description.',
				'stylesheet'        => 'test-theme',
				'is_available'      => true,
				'documentation_url' => 'https://example.com/docs',
				'authors'           => [ 'StellarWP' ],
			]
		);

		$this->assertInstanceOf( Theme::class, $feature );
		$this->assertSame( 'test-theme', $feature->get_slug() );
		$this->assertSame( 'Kadence', $feature->get_group() );
		$this->assertSame( 'Tier 2', $feature->get_tier() );
		$this->assertSame( 'Test Theme', $feature->get_name() );
		$this->assertSame( 'Test theme description.', $feature->get_description() );
		$this->assertSame( 'theme', $feature->get_type() );
		$this->assertSame( 'test-theme', $feature->get_stylesheet() );
		$this->assertTrue( $feature->is_available() );
		$this->assertSame( 'https://example.com/docs', $feature->get_documentation_url() );
		$this->assertSame( [ 'StellarWP' ], $feature->get_authors() );
	}

	/**
	 * Tests that to_array returns the expected associative array.
	 *
	 * @return void
	 */
	public function test_to_array(): void {
		$feature = new Theme(
			[
				'slug'              => 'test-theme',
				'group'             => 'Kadence',
				'tier'              => 'Tier 2',
				'name'              => 'Test Theme',
				'description'       => 'Test theme description.',
				'stylesheet'        => 'test-theme',
				'is_available'      => true,
				'documentation_url' => 'https://example.com/docs',
				'authors'           => [ 'StellarWP' ],
			]
		);

		$this->assertSame(
			[
				'slug'              => 'test-theme',
				'group'             => 'Kadence',
				'tier'              => 'Tier 2',
				'name'              => 'Test Theme',
				'description'       => 'Test theme description.',
				'stylesheet'        => 'test-theme',
				'is_available'      => true,
				'documentation_url' => 'https://example.com/docs',
				'authors'           => [ 'StellarWP' ],
				'type'              => 'theme',
			],
			$feature->to_array()
		);
	}

	/**
	 * Tests that to_array round-trips through from_array.
	 *
	 * @return void
	 */
	public function test_to_array_round_trips_through_from_array(): void {
		$data = [
			'slug'              => 'test-theme',
			'group'             => 'Kadence',
			'tier'              => 'Tier 2',
			'name'              => 'Test Theme',
			'description'       => 'Test theme description.',
			'type'              => 'theme',
			'stylesheet'        => 'test-theme',
			'is_available'      => true,
			'documentation_url' => 'https://example.com/docs',
			'authors'           => [ 'StellarWP' ],
			'is_dot_org'        => false,
		];

		$feature = Theme::from_array( $data );

		$this->assertSame( $data, $feature->to_array() );
	}

	/**
	 * Tests that the description defaults to an empty string when omitted.
	 *
	 * @return void
	 */
	public function test_it_defaults_description_to_empty_string(): void {
		$feature = Theme::from_array(
			[
				'slug'         => 'test-theme',
				'group'        => 'Kadence',
				'tier'         => 'Tier 2',
				'name'         => 'Test Theme',
				'stylesheet'   => 'test-theme',
				'is_available' => false,
			]
		);

		$this->assertSame( '', $feature->get_description() );
	}

	/**
	 * Tests that authors defaults to an empty array when omitted.
	 *
	 * @return void
	 */
	public function test_it_defaults_authors_to_empty_array(): void {
		$feature = Theme::from_array(
			[
				'slug'         => 'test-theme',
				'group'        => 'Kadence',
				'tier'         => 'Tier 1',
				'name'         => 'Test Theme',
				'stylesheet'   => 'test-theme',
				'is_available' => true,
			]
		);

		$this->assertSame( [], $feature->get_authors() );
	}

	// -------------------------------------------------------------------------
	// Hard-coded type
	// -------------------------------------------------------------------------

	/**
	 * Tests that the type is always "theme" regardless of constructor arguments.
	 *
	 * @return void
	 */
	public function test_it_always_has_theme_type(): void {
		$feature = new Theme(
			[
				'slug'         => 'test-theme',
				'group'        => 'Kadence',
				'tier'         => 'Tier 2',
				'name'         => 'Test Theme',
				'description'  => 'Test theme description.',
				'stylesheet'   => 'test-theme',
				'is_available' => true,
			]
		);

		$this->assertSame( 'theme', $feature->get_type() );
	}

	// -------------------------------------------------------------------------
	// Theme-specific getters
	// -------------------------------------------------------------------------

	/**
	 * get_stylesheet() returns the stylesheet passed to the constructor.
	 */
	public function test_get_stylesheet_returns_constructor_value(): void {
		$feature = $this->make_feature(
			self::SLUG,
			self::NAME,
			self::DESCRIPTION,
			'my-custom-theme'
		);

		$this->assertSame( 'my-custom-theme', $feature->get_stylesheet() );
	}

	// -------------------------------------------------------------------------
	// get_authors() — ownership verification field
	// -------------------------------------------------------------------------

	/**
	 * get_authors() returns the array passed to the constructor.
	 */
	public function test_get_authors_returns_constructor_value(): void {
		$feature = $this->make_feature(
			self::SLUG,
			self::NAME,
			self::DESCRIPTION,
			self::STYLESHEET,
			[ 'StellarWP' ]
		);

		$this->assertSame( [ 'StellarWP' ], $feature->get_authors() );
	}

	/**
	 * get_authors() allows an empty array (strategy skips verification).
	 */
	public function test_get_authors_allows_empty_array(): void {
		$feature = $this->make_feature(
			self::SLUG,
			self::NAME,
			self::DESCRIPTION,
			self::STYLESHEET,
			[]
		);

		$this->assertSame( [], $feature->get_authors() );
	}

	/**
	 * get_authors() supports multiple author values.
	 */
	public function test_get_authors_supports_multiple_values(): void {
		$authors = [ 'StellarWP', 'Starter Sites' ];
		$feature = $this->make_feature(
			self::SLUG,
			self::NAME,
			self::DESCRIPTION,
			self::STYLESHEET,
			$authors
		);

		$this->assertSame( $authors, $feature->get_authors() );
	}

	// -------------------------------------------------------------------------
	// Installable interface
	// -------------------------------------------------------------------------

	/**
	 * Theme implements the Installable interface.
	 */
	public function test_it_implements_installable(): void {
		$feature = $this->make_feature();

		$this->assertInstanceOf( Installable::class, $feature );
	}

	/**
	 * get_wp_identifier() delegates to get_stylesheet().
	 */
	public function test_get_wp_identifier_delegates_to_stylesheet(): void {
		$feature = $this->make_feature(
			self::SLUG,
			self::NAME,
			self::DESCRIPTION,
			'my-custom-theme'
		);

		$this->assertSame( 'my-custom-theme', $feature->get_wp_identifier() );
		$this->assertSame( $feature->get_stylesheet(), $feature->get_wp_identifier() );
	}

	/**
	 * get_extension_slug() delegates to get_stylesheet().
	 */
	public function test_get_extension_slug_delegates_to_stylesheet(): void {
		$feature = $this->make_feature(
			self::SLUG,
			self::NAME,
			self::DESCRIPTION,
			'my-custom-theme'
		);

		$this->assertSame( 'my-custom-theme', $feature->get_extension_slug() );
		$this->assertSame( $feature->get_stylesheet(), $feature->get_extension_slug() );
	}

	/**
	 * is_dot_org() defaults to false.
	 */
	public function test_is_dot_org_defaults_to_false(): void {
		$feature = $this->make_feature();

		$this->assertFalse( $feature->is_dot_org() );
	}

	/**
	 * is_dot_org() returns true when set.
	 */
	public function test_is_dot_org_returns_true_when_set(): void {
		$feature = new Theme(
			[
				'slug'         => self::SLUG,
				'group'        => self::GROUP,
				'tier'         => self::TIER,
				'name'         => self::NAME,
				'stylesheet'   => self::STYLESHEET,
				'is_available' => true,
				'is_dot_org'   => true,
			]
		);

		$this->assertTrue( $feature->is_dot_org() );
	}

	/**
	 * from_array() populates is_dot_org when provided.
	 */
	public function test_from_array_includes_is_dot_org(): void {
		$feature = Theme::from_array(
			[
				'slug'         => 'test-theme',
				'group'        => 'Kadence',
				'tier'         => 'Tier 1',
				'name'         => 'Test Theme',
				'stylesheet'   => 'test-theme',
				'is_available' => true,
				'is_dot_org'   => true,
			]
		);

		$this->assertTrue( $feature->is_dot_org() );
	}

	// -------------------------------------------------------------------------
	// Full round-trip
	// -------------------------------------------------------------------------

	/**
	 * All getters return the correct values from a single constructor call.
	 */
	public function test_all_getters_return_correct_values(): void {
		$feature = new Theme(
			[
				'slug'              => 'the-slug',
				'group'             => 'Kadence',
				'tier'              => 'Tier 1',
				'name'              => 'The Name',
				'description'       => 'The description.',
				'stylesheet'        => 'the-stylesheet',
				'is_available'      => true,
				'documentation_url' => 'https://example.com/docs',
				'authors'           => [ 'StellarWP', 'Starter Sites' ],
			]
		);

		$this->assertSame( 'the-slug', $feature->get_slug() );
		$this->assertSame( 'Kadence', $feature->get_group() );
		$this->assertSame( 'Tier 1', $feature->get_tier() );
		$this->assertSame( 'The Name', $feature->get_name() );
		$this->assertSame( 'The description.', $feature->get_description() );
		$this->assertSame( 'theme', $feature->get_type() );
		$this->assertTrue( $feature->is_available() );
		$this->assertSame( 'https://example.com/docs', $feature->get_documentation_url() );
		$this->assertSame( 'the-stylesheet', $feature->get_stylesheet() );
		$this->assertSame( [ 'StellarWP', 'Starter Sites' ], $feature->get_authors() );
	}
}
