<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Resources;

use StellarWP\Uplink\Register;
use StellarWP\Uplink\Tests\License_With_Data_Constant;
use StellarWP\Uplink\Tests\Sample_Plugin;
use StellarWP\Uplink\Tests\UplinkTestCase;

final class LicenseTest extends UplinkTestCase {

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

	protected function setUp(): void {
		parent::setUp();

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
}
