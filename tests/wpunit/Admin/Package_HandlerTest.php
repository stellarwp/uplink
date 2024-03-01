<?php declare( strict_types=1 );

namespace wpunit\Admin;

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use StellarWP\Uplink\Admin\Package_Handler;
use StellarWP\Uplink\Tests\UplinkTestCase;
use WP_Upgrader;

final class Package_HandlerTest extends UplinkTestCase {

	use ProphecyTrait;

	/**
	 * @var \WP_Filesystem_Base|\Prophecy\Prophecy\ObjectProphecy
	 */
	private $filesystem;

	protected function setUp(): void {
		parent::setUp();

		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';

		$this->filesystem = $this->prophesize( \WP_Filesystem_Base::class );
	}

	public function test_it_returns_WP_Error_if_the_package_is_empty_string() {
		$upgrader = $this->prophesize( WP_Upgrader::class );

		$sut      = new Package_Handler();
		$filtered = $sut->filter_upgrader_pre_download( false, '', $upgrader->reveal(), [] );

		$this->assertWPError( $filtered );
	}

	public function test_it_returns_WP_Error_if_the_package_is_null() {
		$upgrader = $this->prophesize( WP_Upgrader::class );

		$sut      = new Package_Handler();
		$filtered = $sut->filter_upgrader_pre_download( false, null, $upgrader->reveal(), [] );

		$this->assertWPError( $filtered );
	}

	public function test_it_should_not_filter_the_download_if_the_pu_get_download_flag_is_not_1() {
		$package  = 'http://update.tri.be?pu_get_download=0';
		$upgrader = $this->prophesize( WP_Upgrader::class );

		$sut      = new Package_Handler();
		$filtered = $sut->filter_upgrader_pre_download( false, $package, $upgrader->reveal(), [] );

		$this->assertFalse( $filtered );
	}

	public function it_should_return_WP_Error_if_the_file_was_not_found() {
		$package           = add_query_arg( [ 'pu_get_download' => '1' ], 'http://foo.bar' );
		$upgrader          = $this->getMockBuilder( WP_Upgrader::class )->getMock();
		$upgrader->strings = [ 'download_failed' => 'meh' ];
		$skin              = $this->prophesize( \WP_Upgrader_Skin::class );

		$skin->feedback( 'downloading_package', $package )->shouldBeCalled();

		$GLOBALS['wp_filesystem'] = $this->filesystem->reveal();
		$upgrader->skin 	  	  = $skin->reveal();

		$sut      = new Package_Handler();
		$filtered = $sut->filter_upgrader_pre_download( false, $package, $upgrader, [] );

		$this->assertFalse( $filtered );
	}

	public function it_should_move_the_file_and_return_a_shorter_named_version_of_it() {
		$url      = wp_get_attachment_url( $this->factory()->attachment->create_upload_object( codecept_data_dir( 'some-file.txt' ) ) );
		$package  = add_query_arg( [ 'pu_get_download' => '1' ], $url );
		$upgrader = $this->getMockBuilder( WP_Upgrader::class )->getMock();
		$skin     = $this->prophesize( \WP_Upgrader_Skin::class );

		$skin->feedback( 'downloading_package', $package )->shouldBeCalled();

		$upgrader->skin      = $skin->reveal();
		$real_temp_file_name = '';
		$destination_file    = '';

		$this->filesystem->move( Argument::type( 'string' ), Argument::type( 'string' ) )->will( function ( $args ) use (
			&$real_temp_file_name,
			&$destination_file
		) {
			$real_temp_file_name = $args[0];
			$destination_file    = $args[1];

			unlink( $args[0] );

			return true;
		} );

		$sut      = new Package_Handler();
		$filtered = $sut->filter_upgrader_pre_download( false, $package, $upgrader, [] );

		$expected_dir           = dirname( $real_temp_file_name );
		$expected_file_exension = pathinfo( $real_temp_file_name, PATHINFO_EXTENSION );
		$expected_file_basename = substr( md5( $real_temp_file_name ), 0, 5 ) . '.' . $expected_file_exension;
		$expected_filename      = $expected_dir . '/' . $expected_file_basename;
		$this->assertEquals( $expected_filename, $destination_file );
		$this->assertEquals( $destination_file, $filtered );
	}

}
