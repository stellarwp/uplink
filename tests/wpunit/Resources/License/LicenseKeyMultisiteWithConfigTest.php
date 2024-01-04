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
 * Test different multisite licensing configurations and where the license key is stored
 * and retrieved.
 *
 * Without any configuration, every subsite is treated as their own site, requiring
 * their own license key.
 */
final class LicenseKeyMultisiteWithConfigTest extends UplinkTestCase {

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

		// No local key stored.
		$this->assertEmpty( $this->single_storage->get( $resource ) );

		// File based key returned.
		$this->assertSame( 'file-based-license-key', $resource->get_license_key() );
	}

	/**
	 * @env multisite
	 */
	public function test_it_gets_single_site_license_key_while_network_activated(): void {
		$this->assertTrue( is_multisite() );
		$this->assertEmpty( $this->resource->get_license_key() );

		$this->single_storage->store( $this->resource, 'local-key' );
		$this->network_storage->store( $this->resource, 'network-key' );
		$this->assertSame( 'network-key', $this->network_storage->get( $this->resource ) );

		$this->assertSame( 'local-key', $this->resource->get_license_key() );
	}

	/**
	 * @env multisite
	 */
	public function test_it_gets_single_site_license_key_with_no_multisite_configuration(): void {
		$this->assertTrue( is_multisite() );
		$this->assertEmpty( $this->resource->get_license_key() );

		// Create a subsite.
		$sub_site_id = wpmu_create_blog( 'wordpress.test', '/sub1', 'Test Subsite', 1 );
		$this->assertNotInstanceOf( WP_Error::class, $sub_site_id );
		$this->assertGreaterThan( 1, $sub_site_id );

		switch_to_blog( $sub_site_id );

		$this->single_storage->store( $this->resource, 'local-key' );
		$this->assertSame( 'local-key', $this->single_storage->get( $this->resource ) );

		$this->network_storage->store( $this->resource, 'network-key' );

		// Local key returned.
		$this->assertSame( 'local-key', $this->resource->get_license_key() );
	}

	/**
	 * @env multisite
	 */
	public function test_it_gets_single_site_license_key_with_all_multisite_types(): void {
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

		foreach ( $sites as $site ) {
			$id = wpmu_create_blog( $site['domain'], $site['path'], $site['name'], 1 );
			$this->assertNotInstanceOf( WP_Error::class, $id );
			$this->assertGreaterThan( 1, $id );

			switch_to_blog( $id );

			$this->assertEmpty( $this->single_storage->get( $this->resource ) );
			$this->single_storage->store( $this->resource, 'local-key' );

			$this->assertSame( 'local-key', $this->resource->get_license_key() );
		}
	}

	/**
	 * @env multisite
	 */
	public function test_it_gets_fallback_file_license_key_with_no_multisite_configuration(): void {
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

		// No local key.
		$this->assertEmpty( $this->single_storage->get( $resource ) );

		// Store network key, but it should never be fetched in this scenario.
		$this->network_storage->store( $resource, 'network-key' );
		$this->assertSame( 'network-key', $this->network_storage->get( $resource ) );

		// File based key returned.
		$this->assertSame( 'file-based-license-key', $resource->get_license_key() );
	}

	/**
	 * @env multisite
	 */
	public function test_it_gets_network_license_key_with_subfolders_configured(): void {
		$this->assertTrue( is_multisite() );
		$this->assertEmpty( $this->resource->get_license_key() );

		Config::allow_site_level_licenses_for_subfolder_multisite( true );

		// Create a subsite.
		$sub_site_id = wpmu_create_blog( 'wordpress.test', '/sub1', 'Test Subsite', 1 );
		$this->assertNotInstanceOf( WP_Error::class, $sub_site_id );
		$this->assertGreaterThan( 1, $sub_site_id );

		switch_to_blog( $sub_site_id );

		$this->single_storage->store( $this->resource, 'local-key' );
		$this->assertSame( 'local-key', $this->single_storage->get( $this->resource ) );

		$this->network_storage->store( $this->resource, 'network-key-subfolder' );

		$this->assertSame( 'network-key-subfolder', $this->resource->get_license_key() );
	}

	/**
	 * @env multisite
	 */
	public function test_it_gets_fallback_file_license_key_with_subfolders_configured(): void {
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

		Config::allow_site_level_licenses_for_subfolder_multisite( true );

		// Create a subsite.
		$sub_site_id = wpmu_create_blog( 'wordpress.test', '/sub1', 'Test Subsite', 1 );
		$this->assertNotInstanceOf( WP_Error::class, $sub_site_id );
		$this->assertGreaterThan( 1, $sub_site_id );

		switch_to_blog( $sub_site_id );

		// Store a local key, but it should never be fetched in this scenario.
		$this->single_storage->store( $resource, 'local-key' );
		$this->assertSame( 'local-key', $this->single_storage->get( $resource ) );

		// No network key stored.
		$this->assertEmpty( $this->network_storage->get( $resource ) );

		// File based key returned.
		$this->assertSame( 'file-based-license-key', $resource->get_license_key() );
	}

	/**
	 * @env multisite
	 */
	public function test_it_gets_network_license_key_with_subdomains_configured(): void {
		$this->assertTrue( is_multisite() );
		$this->assertEmpty( $this->resource->get_license_key() );

		// Only subdomains of the main site are licensed.
		Config::allow_site_level_licenses_for_subdomain_multisite( true );

		// Create a subsite.
		$sub_site_id = wpmu_create_blog( 'temp.wordpress.test', '/', 'Test Subsite', 1 );
		$this->assertNotInstanceOf( WP_Error::class, $sub_site_id );
		$this->assertGreaterThan( 1, $sub_site_id );

		switch_to_blog( $sub_site_id );

		$this->single_storage->store( $this->resource, 'local-key' );
		$this->assertSame( 'local-key', $this->single_storage->get( $this->resource ) );

		$this->network_storage->store( $this->resource, 'network-key-subdomain' );

		$this->assertSame( 'network-key-subdomain', $this->resource->get_license_key() );
	}

	/**
	 * @env multisite
	 */
	public function test_it_gets_network_license_key_with_domain_mapping_configured(): void {
		$this->assertTrue( is_multisite() );
		$this->assertEmpty( $this->resource->get_license_key() );

		// Only custom subsite domains are licensed.
		Config::allow_site_level_licenses_for_mapped_domain_multisite( true );

		// Create a subsite.
		$sub_site_id = wpmu_create_blog( 'wordpress.custom', '/', 'Test Subsite', 1 );
		$this->assertNotInstanceOf( WP_Error::class, $sub_site_id );
		$this->assertGreaterThan( 1, $sub_site_id );

		switch_to_blog( $sub_site_id );

		$this->single_storage->store( $this->resource, 'local-key' );
		$this->assertSame( 'local-key', $this->single_storage->get( $this->resource ) );

		$this->network_storage->store( $this->resource, 'network-key-domain' );

		$this->assertSame( 'network-key-domain', $this->resource->get_license_key() );
	}

	/**
	 * @env multisite
	 */
	public function test_it_gets_network_license_key_with_all_multisite_types_enabled(): void {
		$this->assertTrue( is_multisite() );
		$this->assertEmpty( $this->resource->get_license_key() );

		Config::allow_site_level_licenses_for_subfolder_multisite( true );
		Config::allow_site_level_licenses_for_subdomain_multisite( true );
		Config::allow_site_level_licenses_for_mapped_domain_multisite( true );

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

		foreach ( $sites as $site ) {
			$id = wpmu_create_blog( $site['domain'], $site['path'], $site['name'], 1 );
			$this->assertNotInstanceOf( WP_Error::class, $id );
			$this->assertGreaterThan( 1, $id );

			switch_to_blog( $id );

			$this->assertEmpty( $this->single_storage->get( $this->resource ) );
			$this->single_storage->store( $this->resource, 'local-key' );
			$this->assertSame( 'local-key', $this->single_storage->get( $this->resource ) );

			$this->assertSame( 'network-key', $this->resource->get_license_key() );
		}
	}

	/**
	 * We allow only subfolder network licensing, but we check the license on a subsite with a custom domain.
	 *
	 * @env multisite
	 */
	public function test_it_gets_local_license_key_when_from_a_multisite_type_that_is_not_enabled(): void {
		$this->assertTrue( is_multisite() );
		$this->assertEmpty( $this->resource->get_license_key() );

		// Only subfolders are network licensed.
		Config::allow_site_level_licenses_for_subfolder_multisite( true );

		// Create a subsite with a custom domain.
		$sub_site_id = wpmu_create_blog( 'wordpress.custom', '/', 'Test Subsite', 1 );
		$this->assertNotInstanceOf( WP_Error::class, $sub_site_id );
		$this->assertGreaterThan( 1, $sub_site_id );

		switch_to_blog( $sub_site_id );

		$this->single_storage->store( $this->resource, 'local-key' );
		$this->assertSame( 'local-key', $this->single_storage->get( $this->resource ) );

		$this->network_storage->store( $this->resource, 'network-key-domain' );

		// Local key returned.
		$this->assertSame( 'local-key', $this->resource->get_license_key() );
	}

}