<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\API\V3;

use StellarWP\Uplink\API\V3\Auth\Token_Authorizer;
use StellarWP\Uplink\API\V3\Contracts\Client_V3;
use StellarWP\Uplink\Tests\UplinkTestCase;

final class AuthorizerTest extends UplinkTestCase {

	public function test_it_authorizes_a_valid_token(): void {
		$clientMock = $this->makeEmpty( Client_V3::class, [
			'get' => static function () {
				return [
					'response' => [
						'code' => 200,
					],
					'body'     => json_decode( '{"success":true,"data":{"status":200,"message":"Authorized"}}', true ),
				];
			},
		] );

		$this->container->bind( Client_V3::class, $clientMock );

		$authorizer = $this->container->get( Token_Authorizer::class )->is_authorized( '1234', 'dc2c98d9-9ff8-4409-bfd2-a3cce5b5c840', 'test.com' );

		$this->assertTrue( $authorizer );
	}

	public function test_it_does_not_authorize_an_invalid_license_key(): void {
		$clientMock = $this->makeEmpty( Client_V3::class, [
			'get' => static function () {
				return [
					'response' => [
						'code' => 404,
					],
					'body'     => json_decode( '{"success":false,"data":{"status":404,"message":"License Key Not Found"}}', true ),
				];
			},
		] );

		$this->container->bind( Client_V3::class, $clientMock );

		$authorizer = $this->container->get( Token_Authorizer::class )->is_authorized( 'invalid-license-key', 'dc2c98d9-9ff8-4409-bfd2-a3cce5b5c840', 'test.com' );

		$this->assertFalse( $authorizer );
	}

	public function test_it_does_not_authorize_an_invalid_token(): void {
		$clientMock = $this->makeEmpty( Client_V3::class, [
			'get' => static function () {
				return [
					'response' => [
						'code' => 401,
					],
					'body'     => json_decode( '{"success":false,"data":{"status":401,"message":"Unauthorized"}}', true ),
				];
			},
		] );

		$this->container->bind( Client_V3::class, $clientMock );

		$authorizer = $this->container->get( Token_Authorizer::class )->is_authorized( '1234', 'invalid', 'test.com' );

		$this->assertFalse( $authorizer );
	}

}
