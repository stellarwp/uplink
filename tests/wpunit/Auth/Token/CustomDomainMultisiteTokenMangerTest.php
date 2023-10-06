<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Auth\Token;

use StellarWP\Uplink\Auth\Token\Contracts\Token_Manager;
use StellarWP\Uplink\Config;
use StellarWP\Uplink\Tests\UplinkTestCase;
use StellarWP\Uplink\Uplink;
use WP_Error;

final class CustomDomainMultisiteTokenMangerTest extends UplinkTestCase {

	/***
	 * @var Token_Manager
	 */
	private $token_manager;

	protected function setUp(): void {
		parent::setUp();

		Config::set_token_auth_prefix( 'kadence_' );

		// Run init again to reload the Token/Provider.
		Uplink::init();

		// Main test domain is wordpress.test, create a completely custom domain.
		$sub_site_id = wpmu_create_blog( 'custom.test', '/', 'Test Subsite', 1 );

		$this->assertNotInstanceOf( WP_Error::class, $sub_site_id );
		$this->assertGreaterThan( 1, $sub_site_id );

		switch_to_blog( $sub_site_id );

		$this->token_manager = $this->container->get( Token_Manager::class );
	}

	/**
	 * @env multisite
	 */
	public function test_it_stores_and_retrieves_a_token_from_the_network(): void {
		$this->assertTrue( is_multisite() );

		$this->assertNull( $this->token_manager->get() );

		$token = 'cd4b77be-985f-4737-89b7-eaa13b335fe8';

		$this->assertTrue( $this->token_manager->store( $token ) );

		$this->assertSame( $token, $this->token_manager->get() );

		$this->assertSame( $token, get_network_option( get_current_network_id(), $this->token_manager->option_name() ) );
		$this->assertEmpty( get_option( $this->token_manager->option_name() ) );
	}

	/**
	 * @env multisite
	 */
	public function test_it_deletes_a_token(): void {
		$this->assertNull( $this->token_manager->get() );

		$token = 'b5aad022-71ca-4b29-85c2-70da5c8a5779';

		$this->assertTrue( $this->token_manager->store( $token ) );

		$this->assertSame( $token, $this->token_manager->get() );

		$this->token_manager->delete();

		$this->assertNull( $this->token_manager->get() );
		$this->assertEmpty( get_network_option( get_current_network_id(), $this->token_manager->option_name() ) );
	}

	/**
	 * @env multisite
	 */
	public function test_it_does_not_store_an_empty_token(): void {
		$this->assertNull( $this->token_manager->get() );

		$this->assertFalse( $this->token_manager->store( '' ) );

		$this->assertNull( $this->token_manager->get() );
	}

	/**
	 * @env multisite
	 */
	public function test_it_validates_proper_tokens(): void {
		$tokens = [
			'93280f34-9054-42fb-8f90-e22830eb3225',
			'565435c0-f601-4ba0-ae4e-982b83460f34',
			'a6e8d999-8b55-47ed-8798-0adb608083a6',
			'21897516-419a-4a4c-b0d5-9e21e6506421',
			'09f7f9ea-f931-4600-9739-97fa6ac0d454'
		];

		foreach ( $tokens as $token ) {
			$this->assertTrue( $this->token_manager->validate( $token ) );
		}
	}

	/**
	 * @env multisite
	 */
	public function test_it_does_validate_invalid_tokens(): void {
		$tokens = [
			'4c79d900-5656-11ee-8c99-0242ac120002',
			'6d12a782-5656-11ee-8c99-0242ac120002',
			'invalid',
			'',
		];

		foreach ( $tokens as $token ) {
			$this->assertFalse( $this->token_manager->validate( $token ) );
		}
	}

}
