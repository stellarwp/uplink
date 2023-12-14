<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\License;

use RuntimeException;
use StellarWP\Uplink\Config;
use StellarWP\Uplink\Enums\License_Strategy;
use StellarWP\Uplink\License\License_Key_Strategy_Factory;
use StellarWP\Uplink\License\Strategies\Global_License_Key_Strategy;
use StellarWP\Uplink\License\Strategies\Network_Only_License_Key_Strategy;
use StellarWP\Uplink\License\Strategies\Single_Site_License_Key_Strategy;
use StellarWP\Uplink\Register;
use StellarWP\Uplink\Resources\Resource;
use StellarWP\Uplink\Tests\Sample_Plugin;
use StellarWP\Uplink\Tests\UplinkTestCase;
use WP_Error;

final class LicenseKeyFactoryTest extends UplinkTestCase {

	/**
	 * @var Resource
	 */
	private $resource;

	/**
	 * @var License_Key_Strategy_Factory
	 */
	private $factory;

	protected function setUp(): void {
		parent::setUp();

		// Register the sample plugin as a developer would in their plugin.
		$this->resource = Register::plugin(
			'sample',
			'Lib Sample',
			'1.0.10',
			'uplink/index.php',
			Sample_Plugin::class
		);

		$this->factory = $this->container->get( License_Key_Strategy_Factory::class );
	}

	public function test_it_gets_the_default_strategy(): void {
		$this->assertInstanceOf(
			Global_License_Key_Strategy::class,
			$this->factory->make( $this->resource )
		);
	}

	public function test_it_throws_exception_with_invalid_license_key_strategy(): void {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Invalid config license strategy provided.' );

		Config::set_license_key_strategy( 'invalid' );

		$this->factory->make( $this->resource );
	}

	public function test_it_gets_the_single_site_license_key_strategy(): void {
		Config::set_license_key_strategy( License_Strategy::ISOLATED );

		$this->assertInstanceOf(
			Single_Site_License_Key_Strategy::class,
			$this->factory->make( $this->resource )
		);
	}

	/**
	 * @env multisite
	 */
	public function test_it_gets_the_single_site_license_key_strategy_when_in_multisite_without_configuration(): void {
		$this->assertTrue( is_multisite() );

		// Mock our sample plugin is network activated, otherwise license key check fails.
		$this->mock_activate_plugin( 'uplink/index.php', true );

		Config::set_license_key_strategy( License_Strategy::ISOLATED );

		// Create a subsite.
		$sub_site_id = wpmu_create_blog( 'wordpress.test', '/sub1', 'Test Subsite', 1 );
		$this->assertNotInstanceOf( WP_Error::class, $sub_site_id );
		$this->assertGreaterThan( 1, $sub_site_id );

		switch_to_blog( $sub_site_id );

		$this->assertInstanceOf(
			Single_Site_License_Key_Strategy::class,
			$this->factory->make( $this->resource )
		);
	}

	/**
	 * @env multisite
	 */
	public function test_it_gets_network_license_key_strategy_with_subfolders_configured(): void {
		$this->assertTrue( is_multisite() );

		// Mock our sample plugin is network activated, otherwise license key check fails.
		$this->mock_activate_plugin( 'uplink/index.php', true );

		Config::set_license_key_strategy( License_Strategy::ISOLATED );
		Config::allow_site_level_licenses_for_subfolder_multisite( true );

		// Create a subsite.
		$sub_site_id = wpmu_create_blog( 'wordpress.test', '/sub1', 'Test Subsite', 1 );
		$this->assertNotInstanceOf( WP_Error::class, $sub_site_id );
		$this->assertGreaterThan( 1, $sub_site_id );

		switch_to_blog( $sub_site_id );

		$this->assertInstanceOf(
			Network_Only_License_Key_Strategy::class,
			$this->factory->make( $this->resource )
		);
	}

	/**
	 * @env multisite
	 */
	public function test_it_gets_network_license_key_strategy_with_subdomains_configured(): void {
		$this->assertTrue( is_multisite() );

		// Mock our sample plugin is network activated, otherwise license key check fails.
		$this->mock_activate_plugin( 'uplink/index.php', true );

		Config::set_license_key_strategy( License_Strategy::ISOLATED );
		Config::allow_site_level_licenses_for_subdomain_multisite( true );

		// Create a subsite.
		$sub_site_id = wpmu_create_blog( 'temp.wordpress.test', '/', 'Test Subsite', 1 );
		$this->assertNotInstanceOf( WP_Error::class, $sub_site_id );
		$this->assertGreaterThan( 1, $sub_site_id );

		switch_to_blog( $sub_site_id );

		$this->assertInstanceOf(
			Network_Only_License_Key_Strategy::class,
			$this->factory->make( $this->resource )
		);
	}

	/**
	 * @env multisite
	 */
	public function test_it_gets_network_license_key_strategy_with_domain_mapping_configured(): void {
		$this->assertTrue( is_multisite() );

		// Mock our sample plugin is network activated, otherwise license key check fails.
		$this->mock_activate_plugin( 'uplink/index.php', true );

		Config::set_license_key_strategy( License_Strategy::ISOLATED );
		Config::allow_site_level_licenses_for_mapped_domain_multisite( true );

		// Create a subsite.
		$sub_site_id = wpmu_create_blog( 'wordpress.custom', '/', 'Test Subsite', 1 );
		$this->assertNotInstanceOf( WP_Error::class, $sub_site_id );
		$this->assertGreaterThan( 1, $sub_site_id );

		switch_to_blog( $sub_site_id );

		$this->assertInstanceOf(
			Network_Only_License_Key_Strategy::class,
			$this->factory->make( $this->resource )
		);
	}

}
