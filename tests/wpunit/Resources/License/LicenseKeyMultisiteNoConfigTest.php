<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Resources\License;

use StellarWP\Uplink\Config;
use StellarWP\Uplink\License\Manager\License_Handler;
use StellarWP\Uplink\License\Storage\Local_Storage;
use StellarWP\Uplink\License\Storage\Network_Storage;
use StellarWP\Uplink\Register;
use StellarWP\Uplink\Resources\Resource;
use StellarWP\Uplink\Tests\Sample_Plugin;
use StellarWP\Uplink\Tests\Sample_Plugin_Helper;
use StellarWP\Uplink\Tests\UplinkTestCase;
use WP_Error;

/**
 * No multisite options have been configured.
 *
 * @see Config
 */
final class LicenseKeyMultisiteNoConfigTest extends UplinkTestCase {

	/**
	 * The resource slug.
	 *
	 * @var string
	 */
	private $slug = 'sample';

	/**
	 * @var Resource
	 */
	private $resource;

	/**
	 * Directly access single site license storage.
	 *
	 * @var Local_Storage
	 */
	private $single_storage;

	/**
	 * Directly access network license storage.
	 *
	 * @var Network_Storage
	 */
	private $network_storage;

	protected function setUp(): void {
		parent::setUp();

		// Register the sample plugin as a developer would in their plugin.
		$this->resource = Register::plugin(
			$this->slug,
			'Lib Sample',
			'1.0.10',
			'uplink/index.php',
			Sample_Plugin::class
		);

		// Mock our sample plugin is network activated, otherwise license key check fails.
		$this->mock_activate_plugin( 'uplink/index.php', true );

		$this->container->get( License_Handler::class )->disable_cache();

		$this->single_storage  = $this->container->get( Local_Storage::class );
		$this->network_storage = $this->container->get( Network_Storage::class );
	}

	/**
	 * @env multisite
	 */
	public function test_it_gets_single_site_license_key_on_main_site(): void {
		$this->assertTrue( is_multisite() );
		$this->assertEmpty( $this->resource->get_license_key() );

		$this->single_storage->store( $this->resource, 'local-key' );

		$this->assertSame( 'local-key', $this->resource->get_license_key() );
	}

	/**
	 * @env multisite
	 */
	public function test_it_gets_single_site_fallback_file_license_key_on_main_site(): void {
		$this->assertTrue( is_multisite() );
		$slug = 'sample-with-license';

		// Register the sample plugin as a developer would in their plugin.
		$resource = Register::plugin(
			$slug,
			'Lib Sample With License',
			'1.2.0',
			'uplink/index.php',
			Sample_Plugin::class,
			Sample_Plugin_Helper::class
		);

		// No local key stored.
		$this->assertEmpty( $this->single_storage->get( $resource ) );

		// File based key returned.
		$this->assertSame( 'file-based-license-key', $resource->get_license_key() );
	}

	/**
	 * @env multisite
	 */
	public function test_it_gets_local_license_key_with_existing_network_key(): void {
		$this->assertTrue( is_multisite() );
		$this->assertEmpty( $this->resource->get_license_key() );

		// Create a subsite.
		$sub_site_id = wpmu_create_blog( 'wordpress.test', '/sub1', 'Test Subsite', 1 );
		$this->assertNotInstanceOf( WP_Error::class, $sub_site_id );
		$this->assertGreaterThan( 1, $sub_site_id );

		switch_to_blog( $sub_site_id );

		$this->single_storage->store( $this->resource, 'local-key' );

		$this->network_storage->store( $this->resource, 'network-key' );
		$this->assertSame( 'network-key', $this->network_storage->get( $this->resource ) );

		// Local key returned.
		$this->assertSame( 'local-key', $this->resource->get_license_key() );
	}

	/**
	 * @env multisite
	 */
	public function test_it_gets_local_license_key_with_no_network_key(): void {
		$this->assertTrue( is_multisite() );
		$this->assertEmpty( $this->resource->get_license_key() );

		// Create a subsite.
		$sub_site_id = wpmu_create_blog( 'wordpress.test', '/sub1', 'Test Subsite', 1 );
		$this->assertNotInstanceOf( WP_Error::class, $sub_site_id );
		$this->assertGreaterThan( 1, $sub_site_id );

		switch_to_blog( $sub_site_id );

		$this->single_storage->store( $this->resource, 'local-key' );
		$this->assertSame( 'local-key', $this->single_storage->get( $this->resource ) );

		// No network key stored.
		$this->assertEmpty( $this->network_storage->get( $this->resource ) );

		// Local key returned.
		$this->assertSame( 'local-key', $this->resource->get_license_key() );
	}

	/**
	 * @env multisite
	 */
	public function test_it_gets_file_license_key_no_local_or_network_key(): void {
		$this->assertTrue( is_multisite() );

		$slug = 'sample-with-license';

		// Register the sample plugin as a developer would in their plugin.
		$resource = Register::plugin(
			$slug,
			'Lib Sample With License',
			'1.2.0',
			'uplink/index.php',
			Sample_Plugin::class,
			Sample_Plugin_Helper::class
		);

		// Create a subsite.
		$sub_site_id = wpmu_create_blog( 'wordpress.test', '/sub1', 'Test Subsite', 1 );
		$this->assertNotInstanceOf( WP_Error::class, $sub_site_id );
		$this->assertGreaterThan( 1, $sub_site_id );

		switch_to_blog( $sub_site_id );

		// No local key stored.
		$this->assertEmpty( $this->single_storage->get( $resource ) );

		// No network key stored.
		$this->assertEmpty( $this->network_storage->get( $resource ) );

		// File based key returned.
		$this->assertSame( 'file-based-license-key', $resource->get_license_key() );
	}

}
