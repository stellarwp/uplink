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
use WP_Screen;

/**
 * Test we always get the license key from the network when checking from the network admin.
 */
final class LicenseKeyMultisiteNetworkAdminTest extends UplinkTestCase {

	/**
	 * The resource slug.
	 *
	 * @var string
	 */
	private $slug = 'sample-isolated';

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

		// Allow all multisite modes.
		Config::allow_site_level_licenses_for_subfolder_multisite( true );
		Config::allow_site_level_licenses_for_mapped_domain_multisite( true );
		Config::allow_site_level_licenses_for_subdomain_multisite( true );

		$this->container->get( License_Handler::class )->disable_cache();

		$this->single_storage  = $this->container->get( Local_Storage::class );
		$this->network_storage = $this->container->get( Network_Storage::class );

		// Mock we're in the network dashboard, so is_network_admin() returns true.
		$screen                    = WP_Screen::get( 'dashboard-network' );
		$GLOBALS['current_screen'] = $screen;

		$this->assertTrue( $screen->in_admin( 'network' ) );
	}

	protected function tearDown(): void {
		$GLOBALS['current_screen'] = null;

		parent::tearDown();
	}

	/**
	 * @env multisite
	 */
	public function test_it_gets_network_license_key(): void {
		$this->assertTrue( is_multisite() );
		$this->assertEmpty( $this->resource->get_license_key() );

		$this->network_storage->store( $this->resource, 'network-key' );

		$this->assertSame( 'network-key', $this->resource->get_license_key() );
	}

	/**
	 * @env multisite
	 */
	public function test_it_gets_network_fallback_file_license_key(): void {
		$this->assertTrue( is_multisite() );
		$slug = 'sample-isolated-with-license';

		// Register the sample plugin as a developer would in their plugin.
		$resource = Register::plugin(
			$slug,
			'Lib Sample With License',
			'1.2.0',
			'uplink/index.php',
			Sample_Plugin::class,
			Sample_Plugin_Helper::class
		);

		// No network key stored.
		$this->assertEmpty( $this->network_storage->get( $resource ) );

		// File based key returned.
		$this->assertSame( 'file-based-license-key', $resource->get_license_key() );
	}

	/**
	 * @env multisite
	 */
	public function test_it_gets_network_license_key_with_existing_local_key(): void {
		$this->assertTrue( is_multisite() );
		$this->assertEmpty( $this->resource->get_license_key() );

		$this->single_storage->store( $this->resource, 'local-key' );
		$this->network_storage->store( $this->resource, 'network-key' );
		$this->assertSame( 'local-key', $this->single_storage->get( $this->resource ) );

		$this->assertSame( 'network-key', $this->resource->get_license_key() );
	}

	/**
	 * @env multisite
	 */
	public function test_it_gets_network_license_key_when_all_multisite_types(): void {
		$this->assertTrue( is_multisite() );
		$this->assertEmpty( $this->resource->get_license_key() );

		$sites = [
			[
				'domain' => 'wordpress.test',
				'path'   => '/sub1',
				'name'   => 'Test Subsite 1',
			],
			[
				'domain' => 'temp.wordpress.test',
				'path'   => '/',
				'name'   => 'Test Subdomain Subsite',
			],
			[
				'domain' => 'wordpress.custom',
				'path'   => '/',
				'name'   => 'Test Custom Domain Subsite',
			],
		];

		$this->network_storage->store( $this->resource, 'network-key' );
		$this->assertSame( 'network-key', $this->network_storage->get( $this->resource ) );

		$main_blog_id = get_current_blog_id();

		foreach ( $sites as $site ) {
			$id = wpmu_create_blog( $site['domain'], $site['path'], $site['name'], 1 );
			$this->assertNotInstanceOf( WP_Error::class, $id );
			$this->assertGreaterThan( 1, $id );

			switch_to_blog( $id );

			$this->assertEmpty( $this->single_storage->get( $this->resource ) );
			$this->single_storage->store( $this->resource, 'local-key' );

			switch_to_blog( $main_blog_id );

			$this->assertSame( 'network-key', $this->resource->get_license_key() );
		}
	}

}
