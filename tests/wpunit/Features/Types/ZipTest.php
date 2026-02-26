<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Features\Types;

use StellarWP\Uplink\Features\Types\Zip;
use StellarWP\Uplink\Tests\UplinkTestCase;

final class ZipTest extends UplinkTestCase {

	/**
	 * Standard test values.
	 */
	private const SLUG         = 'stellar-export';
	private const GROUP        = 'LearnDash';
	private const TIER         = 'Tier 1';
	private const NAME         = 'Stellar Export';
	private const DESCRIPTION  = 'Export your data.';
	private const PLUGIN_FILE  = 'stellar-export/stellar-export.php';
	private const DOWNLOAD_URL = 'https://portal.stellarwp.com/stellar-export.zip?secret=abc123';

	/**
	 * Create a Zip feature with configurable values.
	 *
	 * @param string   $slug         Feature slug.
	 * @param string   $name         Display name.
	 * @param string   $description  Description.
	 * @param string   $plugin_file  Plugin file path.
	 * @param string   $download_url Download URL.
	 * @param string[] $authors      Expected plugin authors.
	 *
	 * @return Zip
	 */
	private function make_feature(
		string $slug = self::SLUG,
		string $name = self::NAME,
		string $description = self::DESCRIPTION,
		string $plugin_file = self::PLUGIN_FILE,
		string $download_url = self::DOWNLOAD_URL,
		array $authors = [ 'StellarWP' ]
	): Zip {
		return new Zip( [
			'slug'         => $slug,
			'group'        => self::GROUP,
			'tier'         => self::TIER,
			'name'         => $name,
			'description'  => $description,
			'plugin_file'  => $plugin_file,
			'is_available' => true,
			'download_url' => $download_url,
			'authors'      => $authors,
		] );
	}

	// -------------------------------------------------------------------------
	// from_array() tests
	// -------------------------------------------------------------------------

	/**
	 * Tests a Zip feature can be hydrated from an associative array.
	 *
	 * @return void
	 */
	public function test_it_creates_from_array(): void {
		$feature = Zip::from_array( [
			'slug'              => 'test-feature',
			'group'             => 'LearnDash',
			'tier'              => 'Tier 2',
			'name'              => 'Test Feature',
			'description'       => 'Test feature description.',
			'plugin_file'       => 'test-feature/test-feature.php',
			'is_available'      => true,
			'documentation_url' => 'https://example.com/docs',
			'download_url'      => 'https://example.com/test-feature.zip',
			'authors'           => [ 'StellarWP' ],
		] );

		$this->assertInstanceOf( Zip::class, $feature );
		$this->assertSame( 'test-feature', $feature->get_slug() );
		$this->assertSame( 'LearnDash', $feature->get_group() );
		$this->assertSame( 'Tier 2', $feature->get_tier() );
		$this->assertSame( 'Test Feature', $feature->get_name() );
		$this->assertSame( 'Test feature description.', $feature->get_description() );
		$this->assertSame( 'zip', $feature->get_type() );
		$this->assertSame( 'test-feature/test-feature.php', $feature->get_plugin_file() );
		$this->assertTrue( $feature->is_available() );
		$this->assertSame( 'https://example.com/docs', $feature->get_documentation_url() );
		$this->assertSame( 'https://example.com/test-feature.zip', $feature->get_download_url() );
		$this->assertSame( [ 'StellarWP' ], $feature->get_authors() );
	}

	/**
	 * Tests that to_array returns the expected associative array.
	 *
	 * @return void
	 */
	public function test_to_array(): void {
		$feature = new Zip( [
			'slug'              => 'test-feature',
			'group'             => 'LearnDash',
			'tier'              => 'Tier 2',
			'name'              => 'Test Feature',
			'description'       => 'Test feature description.',
			'plugin_file'       => 'test-feature/test-feature.php',
			'is_available'      => true,
			'documentation_url' => 'https://example.com/docs',
			'download_url'      => 'https://example.com/test-feature.zip',
			'authors'           => [ 'StellarWP' ],
		] );

		$this->assertSame( [
			'slug'              => 'test-feature',
			'group'             => 'LearnDash',
			'tier'              => 'Tier 2',
			'name'              => 'Test Feature',
			'description'       => 'Test feature description.',
			'plugin_file'       => 'test-feature/test-feature.php',
			'is_available'      => true,
			'documentation_url' => 'https://example.com/docs',
			'download_url'      => 'https://example.com/test-feature.zip',
			'authors'           => [ 'StellarWP' ],
			'type'              => 'zip',
		], $feature->to_array() );
	}

	/**
	 * Tests that to_array round-trips through from_array.
	 *
	 * @return void
	 */
	public function test_to_array_round_trips_through_from_array(): void {
		$data = [
			'slug'              => 'test-feature',
			'group'             => 'LearnDash',
			'tier'              => 'Tier 2',
			'name'              => 'Test Feature',
			'description'       => 'Test feature description.',
			'type'              => 'zip',
			'plugin_file'       => 'test-feature/test-feature.php',
			'is_available'      => true,
			'documentation_url' => 'https://example.com/docs',
			'download_url'      => 'https://example.com/test-feature.zip',
			'authors'           => [ 'StellarWP' ],
		];

		$feature = Zip::from_array( $data );

		$this->assertSame( $data, $feature->to_array() );
	}

	/**
	 * Tests that the description defaults to an empty string when omitted from the array.
	 *
	 * @return void
	 */
	public function test_it_defaults_description_to_empty_string(): void {
		$feature = Zip::from_array( [
			'slug'         => 'test-feature',
			'group'        => 'LearnDash',
			'tier'         => 'Tier 2',
			'name'         => 'Test Feature',
			'plugin_file'  => 'test-feature/test-feature.php',
			'is_available' => false,
		] );

		$this->assertSame( '', $feature->get_description() );
	}

	/**
	 * Tests that download_url defaults to an empty string when omitted from the array.
	 *
	 * @return void
	 */
	public function test_it_defaults_download_url_to_empty_string(): void {
		$feature = Zip::from_array( [
			'slug'         => 'test-feature',
			'group'        => 'LearnDash',
			'tier'         => 'Tier 1',
			'name'         => 'Test Feature',
			'plugin_file'  => 'test-feature/test-feature.php',
			'is_available' => true,
		] );

		$this->assertSame( '', $feature->get_download_url() );
	}

	/**
	 * Tests that authors defaults to an empty array when omitted from the array.
	 *
	 * @return void
	 */
	public function test_it_defaults_authors_to_empty_array(): void {
		$feature = Zip::from_array( [
			'slug'         => 'test-feature',
			'group'        => 'LearnDash',
			'tier'         => 'Tier 1',
			'name'         => 'Test Feature',
			'plugin_file'  => 'test-feature/test-feature.php',
			'is_available' => true,
		] );

		$this->assertSame( [], $feature->get_authors() );
	}

	// -------------------------------------------------------------------------
	// Hard-coded type
	// -------------------------------------------------------------------------

	/**
	 * Tests that the type is always "zip" regardless of constructor arguments.
	 *
	 * @return void
	 */
	public function test_it_always_has_zip_type(): void {
		$feature = new Zip( [
			'slug'         => 'test-feature',
			'group'        => 'LearnDash',
			'tier'         => 'Tier 2',
			'name'         => 'Test Feature',
			'description'  => 'Test feature description.',
			'plugin_file'  => 'test-feature/test-feature.php',
			'is_available' => true,
		] );

		$this->assertSame( 'zip', $feature->get_type() );
	}

	// -------------------------------------------------------------------------
	// Zip-specific getters
	// -------------------------------------------------------------------------

	/**
	 * get_plugin_file() returns the plugin file path passed to the constructor.
	 */
	public function test_get_plugin_file_returns_constructor_value(): void {
		$feature = $this->make_feature(
			self::SLUG, self::NAME, self::DESCRIPTION,
			'my-plugin/my-plugin.php'
		);

		$this->assertSame( 'my-plugin/my-plugin.php', $feature->get_plugin_file() );
	}

	/**
	 * get_download_url() returns the download URL passed to the constructor.
	 */
	public function test_get_download_url_returns_constructor_value(): void {
		$url     = 'https://example.com/plugin.zip?token=xyz';
		$feature = $this->make_feature(
			self::SLUG, self::NAME, self::DESCRIPTION,
			self::PLUGIN_FILE, $url
		);

		$this->assertSame( $url, $feature->get_download_url() );
	}

	/**
	 * get_download_url() can return an empty string (validated by the strategy, not the VO).
	 */
	public function test_get_download_url_allows_empty_string(): void {
		$feature = $this->make_feature(
			self::SLUG, self::NAME, self::DESCRIPTION,
			self::PLUGIN_FILE, ''
		);

		$this->assertSame( '', $feature->get_download_url() );
	}

	// -------------------------------------------------------------------------
	// get_authors() — ownership verification field
	// -------------------------------------------------------------------------

	/**
	 * get_authors() returns the array passed to the constructor.
	 */
	public function test_get_authors_returns_constructor_value(): void {
		$feature = $this->make_feature(
			self::SLUG, self::NAME, self::DESCRIPTION,
			self::PLUGIN_FILE, self::DOWNLOAD_URL, [ 'StellarWP' ]
		);

		$this->assertSame( [ 'StellarWP' ], $feature->get_authors() );
	}

	/**
	 * get_authors() allows an empty array (strategy skips verification).
	 */
	public function test_get_authors_allows_empty_array(): void {
		$feature = $this->make_feature(
			self::SLUG, self::NAME, self::DESCRIPTION,
			self::PLUGIN_FILE, self::DOWNLOAD_URL, []
		);

		$this->assertSame( [], $feature->get_authors() );
	}

	/**
	 * get_authors() supports multiple author values.
	 */
	public function test_get_authors_supports_multiple_values(): void {
		$authors = [ 'StellarWP', 'The Events Calendar' ];
		$feature = $this->make_feature(
			self::SLUG, self::NAME, self::DESCRIPTION,
			self::PLUGIN_FILE, self::DOWNLOAD_URL, $authors
		);

		$this->assertSame( $authors, $feature->get_authors() );
	}

	// -------------------------------------------------------------------------
	// get_plugin_slug() — derived from plugin_file
	// -------------------------------------------------------------------------

	/**
	 * get_plugin_slug() returns the directory name from the plugin file path.
	 *
	 * @dataProvider plugin_slug_provider
	 *
	 * @param string $plugin_file    Input plugin file path.
	 * @param string $expected_slug  Expected directory name.
	 */
	public function test_get_plugin_slug_returns_directory_name(
		string $plugin_file,
		string $expected_slug
	): void {
		$feature = $this->make_feature(
			self::SLUG, self::NAME, self::DESCRIPTION,
			$plugin_file
		);

		$this->assertSame( $expected_slug, $feature->get_plugin_slug() );
	}

	/**
	 * Data provider for get_plugin_slug() tests.
	 *
	 * @return array<string, array{string, string}>
	 */
	public function plugin_slug_provider(): array {
		return [
			'standard path'          => [ 'stellar-export/stellar-export.php', 'stellar-export' ],
			'different file name'    => [ 'my-plugin/main.php', 'my-plugin' ],
			'underscored directory'  => [ 'my_plugin/my_plugin.php', 'my_plugin' ],
			'single file no dir'     => [ 'plugin.php', '.' ],
		];
	}

	// -------------------------------------------------------------------------
	// Full round-trip
	// -------------------------------------------------------------------------

	/**
	 * All getters return the correct values from a single constructor call.
	 */
	public function test_all_getters_return_correct_values(): void {
		$feature = new Zip( [
			'slug'              => 'the-slug',
			'group'             => 'LearnDash',
			'tier'              => 'Tier 1',
			'name'              => 'The Name',
			'description'       => 'The description.',
			'plugin_file'       => 'the-slug/the-slug.php',
			'is_available'      => true,
			'documentation_url' => 'https://example.com/docs',
			'download_url'      => 'https://example.com/the-slug.zip',
			'authors'           => [ 'StellarWP', 'The Events Calendar' ],
		] );

		$this->assertSame( 'the-slug', $feature->get_slug() );
		$this->assertSame( 'LearnDash', $feature->get_group() );
		$this->assertSame( 'Tier 1', $feature->get_tier() );
		$this->assertSame( 'The Name', $feature->get_name() );
		$this->assertSame( 'The description.', $feature->get_description() );
		$this->assertSame( 'zip', $feature->get_type() );
		$this->assertTrue( $feature->is_available() );
		$this->assertSame( 'https://example.com/docs', $feature->get_documentation_url() );
		$this->assertSame( 'the-slug/the-slug.php', $feature->get_plugin_file() );
		$this->assertSame( 'https://example.com/the-slug.zip', $feature->get_download_url() );
		$this->assertSame( [ 'StellarWP', 'The Events Calendar' ], $feature->get_authors() );
		$this->assertSame( 'the-slug', $feature->get_plugin_slug() );
	}
}
