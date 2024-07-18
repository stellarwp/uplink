<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\API\V3;

use StellarWP\Uplink\API\V3\Auth\Contracts\Token_Authorizer;
use StellarWP\Uplink\API\V3\Auth\Token_Authorizer_Cache_Decorator;
use StellarWP\Uplink\Tests\UplinkTestCase;

final class AuthorizerCacheTest extends UplinkTestCase {

	public function test_it_binds_the_correct_instance_when_auth_cache_is_enabled(): void {
		$this->assertInstanceOf(
			Token_Authorizer_Cache_Decorator::class,
			$this->container->get( Token_Authorizer::class )
		);
	}

	public function test_it_caches_a_valid_token_response(): void {
		$authorizer_mock = $this->makeEmpty( \StellarWP\Uplink\API\V3\Auth\Token_Authorizer::class, [
			'is_authorized' => static function (): bool {
				return true;
			},
		] );

		$this->container->bind( \StellarWP\Uplink\API\V3\Auth\Token_Authorizer::class, $authorizer_mock );

		$decorator = $this->container->get( Token_Authorizer::class );
		$transient = $decorator->build_transient( [ '1234', 'dc2c98d9-9ff8-4409-bfd2-a3cce5b5c840', 'test.com' ] );

		// No cache should exist.
		$this->assertFalse( get_transient( $transient ) );

		$authorized = $decorator->is_authorized( '1234', 'kadence-blocks-pro','dc2c98d9-9ff8-4409-bfd2-a3cce5b5c840', 'test.com' );

		$this->assertTrue( $authorized );

		// Cache should now be present.
		$this->assertTrue( get_transient( $transient ) );
		$this->assertTrue( $decorator->is_authorized( '1234', 'kadence-blocks-pro','dc2c98d9-9ff8-4409-bfd2-a3cce5b5c840', 'test.com' ) );
	}

	public function test_it_does_not_cache_an_invalid_token_response(): void {
		$authorizer_mock = $this->makeEmpty( \StellarWP\Uplink\API\V3\Auth\Token_Authorizer::class, [
			'is_authorized' => static function (): bool {
				return false;
			},
		] );

		$this->container->bind( \StellarWP\Uplink\API\V3\Auth\Token_Authorizer::class, $authorizer_mock );

		$decorator = $this->container->get( Token_Authorizer::class );
		$transient = $decorator->build_transient( [ '1234', 'dc2c98d9-9ff8-4409-bfd2-a3cce5b5c840', 'test.com' ] );

		// No cache should exist.
		$this->assertFalse( get_transient( $transient ) );

		$authorized = $decorator->is_authorized( '1234', 'kadence-blocks-pro','dc2c98d9-9ff8-4409-bfd2-a3cce5b5c840', 'test.com' );

		$this->assertFalse( $authorized );

		// Cache should still be empty, unfortunately the default is "false" for transients, so this isn't the best test.
		$this->assertFalse( get_transient( $transient ) );
	}

}
