<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Auth\Token;

use StellarWP\Uplink\Auth\Token\Contracts\Token_Manager;
use StellarWP\Uplink\Auth\Token\Managers\Network_Token_Manager;
use StellarWP\Uplink\Auth\Token\Token_Factory;
use StellarWP\Uplink\Config;
use StellarWP\Uplink\Register;
use StellarWP\Uplink\Resources\Collection;
use StellarWP\Uplink\Tests\Sample_Plugin;
use StellarWP\Uplink\Tests\UplinkTestCase;
use StellarWP\Uplink\Uplink;
use WP_Error;

final class SubfolderMultisiteTokenMangerTest extends UplinkTestCase {

	/***
	 * @var Token_Manager
	 */
	private $token_manager;

	protected function setUp(): void {
		parent::setUp();

		Config::set_token_auth_prefix( 'kadence_' );
		Config::allow_site_level_licenses_for_subfolder_multisite( true );

		// Run init again to reload the Token/Provider.
		Uplink::init();

		$slug = 'sample';

		// Register the sample plugin as a developer would in their plugin.
		Register::plugin(
			$slug,
			'Lib Sample',
			'1.0.10',
			'uplink/index.php',
			Sample_Plugin::class
		);

		$this->mock_activate_plugin( 'uplink/index.php', true );

		// Main test domain is wordpress.test, create a subfolder sub-site.
		$sub_site_id = wpmu_create_blog( 'wordpress.test', '/sub1', 'Test Subsite', 1 );

		$this->assertNotInstanceOf( WP_Error::class, $sub_site_id );
		$this->assertGreaterThan( 1, $sub_site_id );

		switch_to_blog( $sub_site_id );

		$plugin = $this->container->get( Collection::class )->offsetGet( $slug );

		$this->token_manager = $this->container->get( Token_Factory::class )
		                                       ->make( $plugin );

		$this->assertInstanceOf( Network_Token_Manager::class, $this->token_manager );
	}

	/**
	 * @env multisite
	 */
	public function test_it_stores_and_retrieves_a_token_from_the_network(): void {
		$this->assertTrue( is_multisite() );

		$this->assertNull( $this->token_manager->get() );

		$token = 'ddbbec78-4439-4180-a6e8-1a63a1df4e2c';

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
	}

	/**
	 * @env multisite
	 */
	public function test_it_does_not_store_an_empty_token(): void {
		$this->assertNull( $this->token_manager->get() );

		$this->assertFalse( $this->token_manager->store( '' ) );

		$this->assertNull( $this->token_manager->get() );
	}

}
