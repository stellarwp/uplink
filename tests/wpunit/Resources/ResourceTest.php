<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Resources;

use StellarWP\Uplink\API\Validation_Response;
use StellarWP\Uplink\Register;
use StellarWP\Uplink\Uplink;

class ResourceTest extends \StellarWP\Uplink\Tests\UplinkTestCase {

	public $resource;

	public function setUp() {
		parent::setUp();

		$root 		    = dirname( __DIR__, 3 );
		$this->resource = Register::plugin(
			'sample',
			'Lib Sample',
			$root . '/plugin.php',
			Uplink::class,
			'1.0.10',
			Uplink::class
		);
	}

	/**
	 * @test
	 */
	/**
	 * @test
	 */
	public function it_should_check_auth_token_valid() {
		$result = $this->resource->has_valid_auth_token( [ 'slug' => 'sample' ] );
		$this->assertFalse( $result );

		update_option( sprintf( 'stellarwp_origin_%s_auth_token', 'sample' ), [
			'token'      => '11111',
			'origin'     => '',
		] );
		add_filter( 'stellarwp/namespace/option_name', function( $name, $entity, $slug ) {
			return 'stellarwp_origin_';
		}, 10, 3);

		update_option( sprintf( 'stellarwp_origin_%s_auth_token', 'sample' ), [
			'token'      => '11111',
			'origin'     => '',
		] );

		$result = $this->resource->has_valid_auth_token( [ 'slug' => 'sample' ] );
		$this->assertTrue( $result );
	}

}
