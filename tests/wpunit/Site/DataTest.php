<?php

namespace StellarWP\Uplink\Tests\Site;

use StellarWP\Uplink;

class DataTest extends \StellarWP\Uplink\Tests\UplinkTestCase {
	public $container;

	public function setUp() {
		parent::setUp();
		$this->container  = Uplink\Config::get_container();
	}

	/**
	 * It should collect base stats.
	 *
	 * @test
	 */
	public function it_should_collect_base_stats() {
		global $wp_version;

		$data = $this->container->make( Uplink\Site\Data::class );
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
	public function it_should_collect_full_stats() {
		add_filter( 'stellarwp/uplink/test/use_full_stats', '__return_true' );

		$data = $this->container->make( Uplink\Site\Data::class );
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

	public function it_should_save_auth_token() {
		$token = json_encode( [
			'token'      => '11111',
			'expiration' => strtotime( '+1 day'),
			'origin'     => 'sample',
		] );

		$data = $this->container->make( Uplink\Site\Data::class );
		$data->save_auth_token( base64_encode( $token ) );

		$options_value = get_option( sprintf( 'stellarwp_origin_%s_auth_token', 'sample' ) );
		$this->assertSame( $token, $options_value );
	}
}
