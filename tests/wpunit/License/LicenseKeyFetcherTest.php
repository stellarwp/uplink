<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\License;

use RuntimeException;
use StellarWP\Uplink\Config;
use StellarWP\Uplink\Enums\License_Strategy;
use StellarWP\Uplink\License\License_Key_Fetcher;
use StellarWP\Uplink\License\Storage\License_Network_Storage;
use StellarWP\Uplink\License\Storage\License_Single_Site_Storage;
use StellarWP\Uplink\Register;
use StellarWP\Uplink\Resources\Resource;
use StellarWP\Uplink\Tests\Sample_Plugin;
use StellarWP\Uplink\Tests\Sample_Plugin_Helper;
use StellarWP\Uplink\Tests\UplinkTestCase;
use WP_Error;

final class LicenseKeyFetcherTest extends UplinkTestCase {

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
	 * @var License_Key_Fetcher
	 */
	private $fetcher;

	/**
	 * Directly access single site license storage.
	 *
	 * @var License_Single_Site_Storage
	 */
	private $single_storage;

	/**
	 * Directly access network license storage.
	 *
	 * @var License_Network_Storage
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

		$this->fetcher         = $this->container->get( License_Key_Fetcher::class );
		$this->single_storage  = $this->container->get( License_Single_Site_Storage::class );
		$this->network_storage = $this->container->get( License_Network_Storage::class );
	}

	public function test_it_throws_exception_with_invalid_license_key_strategy(): void {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Invalid config license strategy provided.' );

		Config::set_license_key_strategy( 'invalid' );

		$this->fetcher->get_key( $this->slug );
	}

	public function test_it_returns_null_with_unknown_resource(): void {
		$this->assertNull( $this->fetcher->get_key( 'unknown-resource' ) );
	}

	public function test_it_gets_single_site_license_key(): void {
		$this->assertNull( $this->fetcher->get_key( $this->slug ) );

		$this->single_storage->store( $this->resource, 'abcdef' );

		$this->assertSame( 'abcdef', $this->fetcher->get_key( $this->slug ) );
	}

	/**
	 * @env multisite
	 */
	public function test_it_gets_the_single_site_license_key_with_global_strategy_and_no_multisite_configuration(): void {
		$this->assertTrue( is_multisite() );
		$this->assertNull( $this->fetcher->get_key( $this->slug ) );

		// Mock our sample plugin is network activated, otherwise license key check fails.
		$this->mock_activate_plugin( 'uplink/index.php', true );

		Config::set_license_key_strategy( License_Strategy::ISOLATED );

		// Create a subsite.
		$sub_site_id = wpmu_create_blog( 'wordpress.test', '/sub1', 'Test Subsite', 1 );
		$this->assertNotInstanceOf( WP_Error::class, $sub_site_id );
		$this->assertGreaterThan( 1, $sub_site_id );

		switch_to_blog( $sub_site_id );

		$this->single_storage->store( $this->resource, 'local-key' );
		$this->assertSame( 'local-key', $this->single_storage->get( $this->resource ) );

		$this->network_storage->store( $this->resource, 'network-key' );

		// Local key returned.
		$this->assertSame( 'local-key', $this->fetcher->get_key( $this->slug ) );
	}

	/**
	 * @env multisite
	 */
	public function test_it_gets_network_license_key_with_isolated_strategy_and_subfolders_configured(): void {
		$this->assertTrue( is_multisite() );
		$this->assertNull( $this->fetcher->get_key( $this->slug ) );

		// Mock our sample plugin is network activated, otherwise license key check fails.
		$this->mock_activate_plugin( 'uplink/index.php', true );

		Config::set_license_key_strategy( License_Strategy::ISOLATED );
		Config::set_network_subfolder_license( true );

		// Create a subsite.
		$sub_site_id = wpmu_create_blog( 'wordpress.test', '/sub1', 'Test Subsite', 1 );
		$this->assertNotInstanceOf( WP_Error::class, $sub_site_id );
		$this->assertGreaterThan( 1, $sub_site_id );

		switch_to_blog( $sub_site_id );

		$this->single_storage->store( $this->resource, 'local-key' );
		$this->assertSame( 'local-key', $this->single_storage->get( $this->resource ) );

		$this->network_storage->store( $this->resource, 'network-key-subfolder' );

		$this->assertSame( 'network-key-subfolder', $this->fetcher->get_key( $this->slug ) );
	}

	/**
	 * @env multisite
	 */
	public function test_it_gets_network_license_key_with_isolated_strategy_and_subdomains_configured(): void {
		$this->assertTrue( is_multisite() );
		$this->assertNull( $this->fetcher->get_key( $this->slug ) );

		// Mock our sample plugin is network activated, otherwise license key check fails.
		$this->mock_activate_plugin( 'uplink/index.php', true );

		Config::set_license_key_strategy( License_Strategy::ISOLATED );
		Config::set_network_subdomain_license( true );

		// Create a subsite.
		$sub_site_id = wpmu_create_blog( 'temp.wordpress.test', '/', 'Test Subsite', 1 );
		$this->assertNotInstanceOf( WP_Error::class, $sub_site_id );
		$this->assertGreaterThan( 1, $sub_site_id );

		switch_to_blog( $sub_site_id );

		$this->single_storage->store( $this->resource, 'local-key' );
		$this->assertSame( 'local-key', $this->single_storage->get( $this->resource ) );

		$this->network_storage->store( $this->resource, 'network-key-subdomain' );

		$this->assertSame( 'network-key-subdomain', $this->fetcher->get_key( $this->slug ) );
	}

	/**
	 * @env multisite
	 */
	public function test_it_gets_network_license_key_with_isolated_strategy_and_domain_mapping_configured(): void {
		$this->assertTrue( is_multisite() );
		$this->assertNull( $this->fetcher->get_key( $this->slug ) );

		// Mock our sample plugin is network activated, otherwise license key check fails.
		$this->mock_activate_plugin( 'uplink/index.php', true );

		Config::set_license_key_strategy( License_Strategy::ISOLATED );
		Config::set_network_domain_mapping_license( true );

		// Create a subsite.
		$sub_site_id = wpmu_create_blog( 'wordpress.custom', '/', 'Test Subsite', 1 );
		$this->assertNotInstanceOf( WP_Error::class, $sub_site_id );
		$this->assertGreaterThan( 1, $sub_site_id );

		switch_to_blog( $sub_site_id );

		$this->single_storage->store( $this->resource, 'local-key' );
		$this->assertSame( 'local-key', $this->single_storage->get( $this->resource ) );

		$this->network_storage->store( $this->resource, 'network-key-domain' );

		$this->assertSame( 'network-key-domain', $this->fetcher->get_key( $this->slug ) );
	}

	/**
	 * @env multisite
	 */
	public function test_it_gets_local_license_key_with_isolated_strategy_and_different_network_strategy(): void {
		$this->assertTrue( is_multisite() );
		$this->assertNull( $this->fetcher->get_key( $this->slug ) );

		// Mock our sample plugin is network activated, otherwise license key check fails.
		$this->mock_activate_plugin( 'uplink/index.php', true );

		Config::set_license_key_strategy( License_Strategy::ISOLATED );
		// Only subfolders are network licensed.
		Config::set_network_subfolder_license( true );

		// Create a subsite with a custom domain.
		$sub_site_id = wpmu_create_blog( 'wordpress.custom', '/', 'Test Subsite', 1 );
		$this->assertNotInstanceOf( WP_Error::class, $sub_site_id );
		$this->assertGreaterThan( 1, $sub_site_id );

		switch_to_blog( $sub_site_id );

		$this->single_storage->store( $this->resource, 'local-key' );
		$this->assertSame( 'local-key', $this->single_storage->get( $this->resource ) );

		$this->network_storage->store( $this->resource, 'network-key-domain' );

		// Local key returned.
		$this->assertSame( 'local-key', $this->fetcher->get_key( $this->slug ) );
	}

	/**
	 * This is the default strategy and how Uplink originally fetched license keys before this
	 * change, by first checking the network, then the single site, then the file based Helper
	 * class.
	 *
	 * @env multisite
	 */
	public function test_it_gets_network_license_key_with_global_license_strategy(): void {
		$this->assertTrue( is_multisite() );
		$this->assertNull( $this->fetcher->get_key( $this->slug ) );

		// Mock our sample plugin is network activated, otherwise license key check fails.
		$this->mock_activate_plugin( 'uplink/index.php', true );

		// Create a subsite.
		$sub_site_id = wpmu_create_blog( 'wordpress.test', '/sub1', 'Test Subsite', 1 );
		$this->assertNotInstanceOf( WP_Error::class, $sub_site_id );
		$this->assertGreaterThan( 1, $sub_site_id );

		switch_to_blog( $sub_site_id );

		$this->single_storage->store( $this->resource, 'local-key' );
		$this->assertSame( 'local-key', $this->single_storage->get( $this->resource ) );

		$this->network_storage->store( $this->resource, 'network-key-legacy' );

		// Network key returned.
		$this->assertSame( 'network-key-legacy', $this->fetcher->get_key( $this->slug ) );
	}

	/**
	 * This is the default strategy and how Uplink originally fetched license keys before this
	 * change, by first checking the network, then the single site, then the file based Helper
	 * class.
	 *
	 * @env multisite
	 */
	public function test_it_gets_local_license_key_with_global_license_strategy(): void {
		$this->assertTrue( is_multisite() );
		$this->assertNull( $this->fetcher->get_key( $this->slug ) );

		// Mock our sample plugin is network activated, otherwise license key check fails.
		$this->mock_activate_plugin( 'uplink/index.php', true );

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
		$this->assertSame( 'local-key', $this->fetcher->get_key( $this->slug ) );
	}

	/**
	 * This is the default strategy and how Uplink originally fetched license keys before this
	 * change, by first checking the network, then the single site, then the file based Helper
	 * class.
	 *
	 * @env multisite
	 */
	public function test_it_gets_file_license_key_with_global_license_strategy(): void {
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

		// Mock our sample plugin is network activated, otherwise license key check fails.
		$this->mock_activate_plugin( 'uplink/index.php', true );

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
		$this->assertSame( 'file-based-license-key', $this->fetcher->get_key( $slug ) );
	}

}
