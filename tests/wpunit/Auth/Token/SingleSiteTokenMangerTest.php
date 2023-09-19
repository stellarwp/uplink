<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Auth\Token;

use StellarWP\Uplink\Auth\Token\Contracts\Token_Manager;
use StellarWP\Uplink\Config;
use StellarWP\Uplink\Tests\UplinkTestCase;
use StellarWP\Uplink\Uplink;

final class SingleSiteTokenMangerTest extends UplinkTestCase {

	/***
	 * @var Token_Manager
	 */
	private $token_manager;

	protected function setUp() {
		parent::setUp();

		Config::set_token_auth_prefix( 'kadence_' );

		// Run init again to reload the Token/Provider.
		Uplink::init();

		$this->token_manager = $this->container->get( Token_Manager::class );
	}

	/**
	 * @env singlesite
	 */
	public function test_it_stores_and_retrieves_a_token(): void {
		$this->assertFalse( is_multisite() );

		$this->assertNull( $this->token_manager->get() );

		$token = 'b0679a2e-b36d-41ca-8121-f43267751938';

		$this->assertTrue( $this->token_manager->store( $token ) );

		$this->assertSame( $token, $this->token_manager->get() );

		$this->assertSame( $token, get_option( $this->token_manager->option_name() ) );

	}

	/**
	 * @env singlesite
	 */
	public function test_it_deletes_a_token(): void {
		$this->assertNull( $this->token_manager->get() );

		$token = 'b5aad022-71ca-4b29-85c2-70da5c8a5779';

		$this->assertTrue( $this->token_manager->store( $token ) );

		$this->assertSame( $token, $this->token_manager->get() );

		$this->token_manager->delete();

		$this->assertNull( $this->token_manager->get() );
	}

	/**
	 * @env singlesite
	 */
	public function test_it_does_not_store_an_empty_token(): void {
		$this->assertNull( $this->token_manager->get() );

		$this->assertFalse( $this->token_manager->store( '' ) );

		$this->assertNull( $this->token_manager->get() );
	}

}
