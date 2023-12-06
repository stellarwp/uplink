<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Site;

use StellarWP\Uplink;
use StellarWP\Uplink\Config;
use StellarWP\Uplink\Tests\UplinkTestCase;
use WP_Error;

class DataTest extends UplinkTestCase {

	/**
	 * It should collect base stats.
	 *
	 * @test
	 */
	public function it_should_collect_base_stats(): void {
		global $wp_version;

		$data = $this->container->get( Uplink\Site\Data::class );
		$stats = $data->get_stats();

		$this->assertArrayHasKey( 'versions', $stats );
		$this->assertArrayHasKey( 'wp', $stats['versions'] );
		$this->assertArrayHasKey( 'multisite', $stats['network'] );
		$this->assertArrayHasKey( 'network_activated', $stats['network'] );
		$this->assertArrayHasKey( 'active_sites', $stats['network'] );
		$this->assertArrayNotHasKey( 'totals', $stats );

		$this->assertEquals( $wp_version, $stats['versions']['wp'] );
	}

	/**
	 * It should collect full stats.
	 *
	 * @test
	 */
	public function it_should_collect_full_stats(): void {
		add_filter( 'stellarwp/uplink/test/use_full_stats', '__return_true' );

		$data = $this->container->get( Uplink\Site\Data::class );
		$stats = $data->get_stats();

		$this->assertArrayHasKey( 'versions', $stats );
		$this->assertArrayHasKey( 'wp', $stats['versions'] );
		$this->assertArrayHasKey( 'multisite', $stats['network'] );
		$this->assertArrayHasKey( 'network_activated', $stats['network'] );
		$this->assertArrayHasKey( 'active_sites', $stats['network'] );
		$this->assertArrayHasKey( 'totals', $stats );

		$this->assertEquals( phpversion(), $stats['versions']['php'] );

		remove_filter( 'stellarwp/uplink/test/use_full_stats', '__return_true' );
	}

	/**
	 * @env singlesite
	 */
	public function test_it_gets_single_site_domain(): void {
		$this->assertFalse( is_multisite() );

		$data = $this->container->get( Uplink\Site\Data::class );

		$this->assertSame( 'wordpress.test', $data->get_domain() );
	}

	/**
	 * If Config::allows_network_subfolder_license() is not enabled, subsites in subfolder
	 * mode must be unique, so include their subfolder path in the domain.
	 *
	 * @see Config::allows_network_subfolder_license()
	 *
	 * @env multisite
	 */
	public function test_it_should_get_multisite_subsite_domain_with_path(): void {
		$this->assertTrue( is_multisite() );

		$data = $this->container->get( Uplink\Site\Data::class );

		// Main test domain is wordpress.test, create a subfolder sub-site.
		$sub_site_id_1 = wpmu_create_blog( 'wordpress.test', '/sub1', 'Test Subsite', 1 );

		$this->assertNotInstanceOf( WP_Error::class, $sub_site_id_1 );
		$this->assertGreaterThan( 1, $sub_site_id_1 );

		// Assert the main site still returns correctly before switching.
		$this->assertSame( 'wordpress.test', $data->get_domain() );

		switch_to_blog( $sub_site_id_1 );

		$this->assertSame( 'wordpress.test/sub1', $data->get_domain() );

		$sub_site_id_2 = wpmu_create_blog( 'wordpress.test', '/sub2', 'Test Subsite 2', 1 );

		$this->assertNotInstanceOf( WP_Error::class, $sub_site_id_2 );
		$this->assertGreaterThan( 1, $sub_site_id_2 );

		switch_to_blog( $sub_site_id_2 );

		$this->assertSame( 'wordpress.test/sub2', $data->get_domain() );
	}

	/**
	 * If the developer configured network subfolder licensing, it should return the main domain.
	 *
	 * @env multisite
	 */
	public function test_it_returns_main_site_url_when_network_subfolders_are_allowed(): void {
		Config::set_network_subfolder_license( true );
		Uplink\Uplink::init();

		$this->assertTrue( is_multisite() );

		$data = $this->container->get( Uplink\Site\Data::class );

		// Main test domain is wordpress.test, create a subfolder sub-site.
		$sub_site_id_1 = wpmu_create_blog( 'wordpress.test', '/sub1', 'Test Subsite', 1 );

		$this->assertNotInstanceOf( WP_Error::class, $sub_site_id_1 );
		$this->assertGreaterThan( 1, $sub_site_id_1 );

		// Assert the main site still returns correctly before switching.
		$this->assertSame( 'wordpress.test', $data->get_domain() );

		switch_to_blog( $sub_site_id_1 );

		$this->assertSame( 'wordpress.test', $data->get_domain() );

		$sub_site_id_2 = wpmu_create_blog( 'wordpress.test', '/sub2', 'Test Subsite 2', 1 );

		$this->assertNotInstanceOf( WP_Error::class, $sub_site_id_2 );
		$this->assertGreaterThan( 1, $sub_site_id_2 );

		switch_to_blog( $sub_site_id_2 );

		$this->assertSame( 'wordpress.test', $data->get_domain() );
	}

	/**
	 * @env multisite
	 */
	public function test_it_gets_domain_with_custom_subsite_domain(): void {
		$this->assertTrue( is_multisite() );

		$data = $this->container->get( Uplink\Site\Data::class );

		// Main test domain is wordpress.test, create a subfolder sub-site.
		$sub_site_id_1 = wpmu_create_blog( 'custom-domain.test', '/', 'Test Subsite', 1 );

		$this->assertNotInstanceOf( WP_Error::class, $sub_site_id_1 );
		$this->assertGreaterThan( 1, $sub_site_id_1 );

		// Assert the main site still returns correctly before switching.
		$this->assertSame( 'wordpress.test', $data->get_domain() );

		switch_to_blog( $sub_site_id_1 );

		$this->assertSame( 'custom-domain.test', $data->get_domain() );
	}

	/**
	 * @env multisite
	 */
	public function test_it_gets_main_domain_with_custom_subsite_domain_and_network_subfolders_enabled(): void {
		Config::set_network_subfolder_license( true );
		Uplink\Uplink::init();

		$this->assertTrue( is_multisite() );

		$data = $this->container->get( Uplink\Site\Data::class );

		// Main test domain is wordpress.test, create a subfolder sub-site.
		$sub_site_id_1 = wpmu_create_blog( 'custom-domain.test', '/', 'Test Subsite', 1 );

		$this->assertNotInstanceOf( WP_Error::class, $sub_site_id_1 );
		$this->assertGreaterThan( 1, $sub_site_id_1 );

		switch_to_blog( $sub_site_id_1 );

		// Should return main domain, not the custom one.
		$this->assertSame( 'wordpress.test', $data->get_domain() );
	}

	/**
	 * @env multisite
	 */
	public function test_it_gets_domain_with_subdomain(): void {
		$this->assertTrue( is_multisite() );

		$data = $this->container->get( Uplink\Site\Data::class );

		// Main test domain is wordpress.test, create a subfolder sub-site.
		$sub_site_id_1 = wpmu_create_blog( 'sub1.wordpress.test', '/', 'Test Subsite', 1 );

		$this->assertNotInstanceOf( WP_Error::class, $sub_site_id_1 );
		$this->assertGreaterThan( 1, $sub_site_id_1 );

		// Assert the main site still returns correctly before switching.
		$this->assertSame( 'wordpress.test', $data->get_domain() );

		switch_to_blog( $sub_site_id_1 );

		$this->assertSame( 'sub1.wordpress.test', $data->get_domain() );
	}

	/**
	 * @env multisite
	 */
	public function test_it_gets_main_domain_with_subdomain_and_network_subfolders_enabled(): void {
		Config::set_network_subfolder_license( true );
		Uplink\Uplink::init();

		$this->assertTrue( is_multisite() );

		$data = $this->container->get( Uplink\Site\Data::class );

		// Main test domain is wordpress.test, create a subfolder sub-site.
		$sub_site_id_1 = wpmu_create_blog( 'sub1.wordpress.test', '/', 'Test Subsite', 1 );

		$this->assertNotInstanceOf( WP_Error::class, $sub_site_id_1 );
		$this->assertGreaterThan( 1, $sub_site_id_1 );

		switch_to_blog( $sub_site_id_1 );

		// Should return main domain, not the custom one.
		$this->assertSame( 'wordpress.test', $data->get_domain() );
	}

}
