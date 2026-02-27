<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Resources;

use StellarWP\Uplink\Register;
use StellarWP\Uplink\Resources\License;
use StellarWP\Uplink\Resources\Plugin;
use StellarWP\Uplink\Tests\License_With_Data_Constant;
use StellarWP\Uplink\Tests\Sample_Plugin;
use StellarWP\Uplink\Tests\UplinkTestCase;
use StellarWP\Uplink\Uplink;

final class LicenseTest extends UplinkTestCase {

	/**
	 * The resource to test license key origin behavior against.
	 *
	 * @var Plugin
	 */
	private $resource;

	/**
	 * Temporary files created during tests.
	 *
	 * @var string[]
	 */
	private $temp_files = [];

	/**
	 * Temporary directories created during tests.
	 *
	 * @var string[]
	 */
	private $temp_dirs = [];

	/**
	 * The plugin directory path used for test resources.
	 *
	 * @var string
	 */
	private $plugin_dir;

	/**
	 * Sets up the test fixture.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->resource = Register::plugin(
			'sample',
			'Lib Sample',
			'1.0.10',
			'uplink/index.php',
			Uplink::class
		);

		$this->plugin_dir = WP_PLUGIN_DIR . '/uplink';

		if ( ! is_dir( $this->plugin_dir ) ) {
			mkdir( $this->plugin_dir, 0755, true );
		}
	}

	protected function tearDown(): void {
		foreach ( $this->temp_files as $file ) {
			if ( file_exists( $file ) ) {
				unlink( $file );
			}
		}

		foreach ( $this->temp_dirs as $dir ) {
			if ( is_dir( $dir ) ) {
				rmdir( $dir );
			}
		}

		$this->temp_files = [];
		$this->temp_dirs  = [];

		parent::tearDown();
	}

	/**
	 * Creates a simple license file that returns the license key.
	 *
	 * @param string $filename     The file name relative to the plugin directory.
	 * @param string $license_key  The license key to embed.
	 */
	private function create_simple_license_file( string $filename, string $license_key ): void {
		$this->create_license_file_with_content(
			$filename,
			"<?php return '" . addslashes( $license_key ) . "';"
		);
	}

	/**
	 * Creates a simple license file with arbitrary PHP content.
	 *
	 * @param string $filename The file name relative to the plugin directory.
	 * @param string $content  The PHP file content.
	 */
	private function create_license_file_with_content( string $filename, string $content ): void {
		$file = $this->plugin_dir . '/' . $filename;
		$dir  = dirname( $file );

		if ( $dir !== $this->plugin_dir && ! is_dir( $dir ) ) {
			mkdir( $dir, 0755, true );
			$this->temp_dirs[] = $dir;
		}

		file_put_contents( $file, $content );
		$this->temp_files[] = $file;
	}

	/**
	 * @test
	 */
	public function it_should_get_key_from_class_data_constant(): void {
		$resource = Register::plugin(
			'class-data-constant',
			'Class Data Constant Plugin',
			'1.0.0',
			'uplink/index.php',
			Sample_Plugin::class,
			License_With_Data_Constant::class
		);

		$this->assertSame( License_With_Data_Constant::DATA, $resource->get_license_key( 'default' ) );
	}

	/**
	 * @test
	 */
	public function it_should_get_key_from_auth_token_file(): void {
		$expected_key = 'test-license-key-12345';
		$this->create_simple_license_file( 'auth-token.php', $expected_key );

		$resource = Register::plugin(
			'auth-token-plugin',
			'Auth Token Plugin',
			'1.0.0',
			'uplink/index.php',
			Sample_Plugin::class,
			'auth-token.php'
		);

		$this->assertSame( $expected_key, $resource->get_license_key( 'default' ) );
	}

	/**
	 * @test
	 */
	public function it_should_get_key_from_plugin_license_file(): void {
		$expected_key = 'another-license-key-67890';
		$this->create_simple_license_file( 'PLUGIN_LICENSE.php', $expected_key );

		$resource = Register::plugin(
			'plugin-license-file',
			'Plugin License File',
			'1.0.0',
			'uplink/index.php',
			Sample_Plugin::class,
			'PLUGIN_LICENSE.php'
		);

		$this->assertSame( $expected_key, $resource->get_license_key( 'default' ) );
	}

	/**
	 * @test
	 */
	public function it_should_get_key_from_simple_file_in_subdirectory(): void {
		$expected_key = 'subdir-license-key-99999';
		$this->create_simple_license_file( 'config/license.php', $expected_key );

		$resource = Register::plugin(
			'subdir-license-plugin',
			'Subdir License Plugin',
			'1.0.0',
			'uplink/index.php',
			Sample_Plugin::class,
			'config/license.php'
		);

		$this->assertSame( $expected_key, $resource->get_license_key( 'default' ) );
	}

	/**
	 * @test
	 */
	public function it_should_return_empty_when_simple_license_file_does_not_exist(): void {
		$resource = Register::plugin(
			'missing-file-plugin',
			'Missing File Plugin',
			'1.0.0',
			'uplink/index.php',
			Sample_Plugin::class,
			'nonexistent-license.php'
		);

		$this->assertSame( '', $resource->get_license_key( 'default' ) );
	}

	/**
	 * @test
	 */
	public function it_should_return_empty_when_file_returns_non_string(): void {
		$this->create_license_file_with_content(
			'bad-license.php',
			"<?php return ['not', 'a', 'string'];"
		);

		$resource = Register::plugin(
			'bad-return-plugin',
			'Bad Return Plugin',
			'1.0.0',
			'uplink/index.php',
			Sample_Plugin::class,
			'bad-license.php'
		);

		$this->assertSame( '', $resource->get_license_key( 'default' ) );
	}

	/**
	 * @test
	 */
	public function it_should_return_empty_when_no_license_class_is_set(): void {
		$resource = Register::plugin(
			'no-license-class-plugin',
			'No License Class Plugin',
			'1.0.0',
			'uplink/index.php',
			Sample_Plugin::class
		);

		$this->assertSame( '', $resource->get_license_key( 'default' ) );
	}

	/**
	 * @test
	 */
	public function it_should_prefer_site_option_over_simple_license_file(): void {
		$file_key   = 'file-license-key';
		$option_key = 'option-license-key';

		$this->create_simple_license_file( 'priority-test.php', $file_key );

		$resource = Register::plugin(
			'priority-test-plugin',
			'Priority Test Plugin',
			'1.0.0',
			'uplink/index.php',
			Sample_Plugin::class,
			'priority-test.php'
		);

		// Set a license key via site option.
		$resource->set_license_key( $option_key );

		// The site option key should take priority over the file key.
		$this->assertSame( $option_key, $resource->get_license_key() );
	}

	/**
	 * @test
	 */
	public function it_should_fall_back_to_simple_license_file_when_no_option_set(): void {
		$file_key = 'fallback-license-key';

		$this->create_simple_license_file( 'fallback-test.php', $file_key );

		$resource = Register::plugin(
			'fallback-test-plugin',
			'Fallback Test Plugin',
			'1.0.0',
			'uplink/index.php',
			Sample_Plugin::class,
			'fallback-test.php'
		);

		// With no site option set, the file key should be returned.
		$this->assertSame( $file_key, $resource->get_license_key() );
	}

	/**
	 * Tests that key_origin is set to 'filter' when the global license_get_key filter changes the key.
	 *
	 * @return void
	 */
	public function test_sets_key_origin_to_filtered_when_key_is_changed_by_filter(): void {
		$filtered_key = 'filtered-license-key-12345';

		add_filter(
			'stellarwp/uplink/test/license_get_key',
			function () use ( $filtered_key ) {
				return $filtered_key;
			} 
		);

		$license = $this->resource->get_license_object();
		$key     = $license->get_key();

		$this->assertSame( $filtered_key, $key );

		$key_origin = $license->get_key_origin();

		$this->assertSame( 'filter', $key_origin );
	}

	/**
	 * Tests that key_origin is set to 'filter' when the slug-specific license_get_key filter changes the key.
	 *
	 * @return void
	 */
	public function test_sets_key_origin_to_filtered_when_key_is_changed_by_slug_specific_filter(): void {
		$filtered_key = 'slug-filtered-license-key-12345';

		add_filter(
			'stellarwp/uplink/test/sample/license_get_key',
			function () use ( $filtered_key ) {
				return $filtered_key;
			} 
		);

		$license = $this->resource->get_license_object();
		$key     = $license->get_key();

		$this->assertSame( $filtered_key, $key );

		$key_origin = $license->get_key_origin();

		$this->assertSame( 'filter', $key_origin );
	}

	/**
	 * Tests that key_origin is not set to 'filter' when the filter returns the key unchanged.
	 *
	 * @return void
	 */
	public function test_does_not_set_key_origin_to_filtered_when_filter_returns_same_key(): void {
		$original_key = 'original-license-key-12345';

		$this->resource->set_license_key( $original_key );

		add_filter(
			'stellarwp/uplink/test/license_get_key',
			function ( $key ) {
				return $key;
			} 
		);

		$license = $this->resource->get_license_object();
		$key     = $license->get_key();

		$this->assertSame( $original_key, $key );

		$key_origin = $license->get_key_origin();

		$this->assertNotSame( 'filter', $key_origin );
	}

	/**
	 * Tests that key_origin is not set to 'filter' when the filter returns an empty string.
	 *
	 * @return void
	 */
	public function test_does_not_set_key_origin_to_filtered_when_filter_returns_empty(): void {
		add_filter(
			'stellarwp/uplink/test/license_get_key',
			function () {
				return '';
			} 
		);

		$license = $this->resource->get_license_object();
		$key     = $license->get_key();

		$this->assertSame( '', $key );

		$key_origin = $license->get_key_origin();

		$this->assertNotSame( 'filter', $key_origin );
	}

	/**
	 * Tests that get_key_origin_code() returns 'f' when the key is changed by the global filter.
	 *
	 * @return void
	 */
	public function test_get_key_origin_code_returns_f_when_key_is_filtered(): void {
		$filtered_key = 'filtered-origin-code-key-12345';

		add_filter(
			'stellarwp/uplink/test/license_get_key',
			function () use ( $filtered_key ) {
				return $filtered_key;
			} 
		);

		$license = $this->resource->get_license_object();
		$license->get_key();

		$this->assertSame( 'f', $license->get_key_origin_code() );
	}

	/**
	 * Tests that get_key_origin_code() returns 'f' when the key is changed by the slug-specific filter.
	 *
	 * @return void
	 */
	public function test_get_key_origin_code_returns_f_when_key_is_filtered_by_slug_specific_filter(): void {
		$filtered_key = 'slug-filtered-origin-code-key-12345';

		add_filter(
			'stellarwp/uplink/test/sample/license_get_key',
			function () use ( $filtered_key ) {
				return $filtered_key;
			} 
		);

		$license = $this->resource->get_license_object();
		$license->get_key();

		$this->assertSame( 'f', $license->get_key_origin_code() );
	}

	/**
	 * Tests that get_key_origin_code() returns 'm' when the key is set via a site option.
	 *
	 * @return void
	 */
	public function test_get_key_origin_code_returns_m_when_key_is_from_site_option(): void {
		$this->resource->set_license_key( 'manual-license-key-12345' );

		// Create a fresh License so get_key() discovers the key from the option.
		$license = new License( $this->resource );
		$license->get_key();

		$this->assertSame( 'm', $license->get_key_origin_code() );
	}

	/**
	 * Tests that get_key_origin_code() returns 'e' when the key comes from a license file.
	 *
	 * @return void
	 */
	public function test_get_key_origin_code_returns_e_when_key_is_from_file(): void {
		$resource = Register::plugin(
			'origin-code-file',
			'Origin Code File Plugin',
			'1.0.0',
			'uplink/index.php',
			Sample_Plugin::class,
			License_With_Data_Constant::class
		);

		$license = $resource->get_license_object();
		$license->get_key();

		$this->assertSame( 'e', $license->get_key_origin_code() );
	}

	/**
	 * Tests that get_key_origin_code() returns 'o' when no key source is found.
	 *
	 * @return void
	 */
	public function test_get_key_origin_code_returns_o_when_no_key_exists(): void {
		$license = $this->resource->get_license_object();
		$license->get_key();

		$this->assertSame( 'o', $license->get_key_origin_code() );
	}

}
