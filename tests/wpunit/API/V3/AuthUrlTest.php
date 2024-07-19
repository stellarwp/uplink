<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\API\V3;

use StellarWP\Uplink\API\V3\Auth\Auth_Url;
use StellarWP\Uplink\API\V3\Auth\Auth_Url_Cache_Decorator;
use StellarWP\Uplink\API\V3\Contracts\Client_V3;
use StellarWP\Uplink\Auth\Auth_Url_Builder;
use StellarWP\Uplink\Storage\Contracts\Storage;
use StellarWP\Uplink\Tests\UplinkTestCase;

final class AuthUrlTest extends UplinkTestCase {

	/**
	 * @var Storage
	 */
	private $storage;

	protected function setUp(): void {
		parent::setUp();

		$this->storage = $this->container->get( Storage::class );
	}

	public function test_the_cache_decorator_is_enabled(): void {
		$auth_url = $this->container->get( \StellarWP\Uplink\API\V3\Auth\Contracts\Auth_Url::class );

		$this->assertInstanceOf( Auth_Url_Cache_Decorator::class, $auth_url );
	}

	public function test_it_gets_an_auth_url(): void {
		$clientMock = $this->makeEmpty( Client_V3::class, [
			'get' => static function () {
				return [
					'response' => [
						'code' => 200,
					],
					'body'   => json_decode( '{"success":true,"data":{"status":200,"auth_url":"https://www.kadencewp.com/account-auth/"}}', true ),
				];
			},
		] );

		$this->container->bind( Client_V3::class, $clientMock );

		$auth_url = $this->container->get( Auth_Url::class )->get( 'kadence-blocks-pro' );

		$this->assertSame( 'https://www.kadencewp.com/account-auth/', $auth_url );
	}

	public function test_it_builds_an_auth_url(): void {
		$clientMock = $this->makeEmpty( Client_V3::class, [
			'get' => static function () {
				return [
					'response' => [
						'code' => 200,
					],
					'body'   => json_decode( '{"success":true,"data":{"status":200,"auth_url":"https://www.kadencewp.com/account-auth/"}}', true ),
				];
			},
		] );

		$this->container->bind( Client_V3::class, $clientMock );

		$auth_url = $this->container->get( Auth_Url_Builder::class )->build( 'kadence-blocks', 'test.com' );

		parse_str( parse_url( $auth_url, PHP_URL_QUERY ), $query_vars );

		$callback = base64_decode( urldecode( $query_vars['uplink_callback'] ), true );

		$this->assertStringStartsWith(
			'http://wordpress.test/wp-admin/index.php?uplink_domain=test.com&uplink_slug=kadence-blocks&_uplink_nonce=',
			$callback
		);
	}

	public function test_it_does_not_get_an_auth_url(): void {
		$clientMock = $this->makeEmpty( Client_V3::class, [
			'get' => static function () {
				return [
					'response' => [
						'code' => 404,
					],
					'body'   => json_decode( '{"success":false,"data":{"status":404,"message":"Auth URL Not Found"}}', true ),
				];
			},
		] );

		$this->container->bind( Client_V3::class, $clientMock );

		$auth_url = $this->container->get( Auth_Url::class )->get( 'kadence-blocks-pro' );

		$this->assertSame( '', $auth_url );
	}

	public function test_it_caches_a_valid_auth_url(): void {
		$clientMock = $this->makeEmpty( Client_V3::class, [
			'get' => static function () {
				return [
					'response' => [
						'code' => 200,
					],
					'body'   => json_decode( '{"success":true,"data":{"status":200,"auth_url":"https://www.kadencewp.com/account-auth/"}}', true ),
				];
			},
		] );

		$this->container->bind( Client_V3::class, $clientMock );

		$auth_url = $this->container->get( Auth_Url_Cache_Decorator::class )->get( 'kadence-blocks-pro' );

		$this->assertSame( 'https://www.kadencewp.com/account-auth/', $auth_url );
		$this->assertSame( 'https://www.kadencewp.com/account-auth/', $this->storage->get( Auth_Url_Cache_Decorator::TRANSIENT_PREFIX . 'kadence_blocks_pro' ) );
	}

	public function test_it_caches_an_empty_auth_url(): void {
		$clientMock = $this->makeEmpty( Client_V3::class, [
			'get' => static function () {
				return [
					'response' => [
						'code' => 404,
					],
					'body'   => json_decode( '{"success":false,"data":{"status":404,"message":"Auth URL Not Found"}}', true ),
				];
			},
		] );

		$this->container->bind( Client_V3::class, $clientMock );

		$auth_url = $this->container->get( Auth_Url_Cache_Decorator::class )->get( 'kadence-blocks-pro' );

		$this->assertSame( '', $auth_url );
		$this->assertSame( '', $this->storage->get( Auth_Url_Cache_Decorator::TRANSIENT_PREFIX . 'kadence_blocks_pro' ) );
	}

}
