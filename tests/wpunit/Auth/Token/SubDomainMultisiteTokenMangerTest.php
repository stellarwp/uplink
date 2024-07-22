<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Auth\Token;

use StellarWP\Uplink\Auth\Token\Contracts\Token_Manager;
use StellarWP\Uplink\Config;
use StellarWP\Uplink\Register;
use StellarWP\Uplink\Resources\Resource;
use StellarWP\Uplink\Tests\Sample_Plugin;
use StellarWP\Uplink\Tests\UplinkTestCase;
use StellarWP\Uplink\Uplink;
use WP_Error;

final class SubDomainMultisiteTokenMangerTest extends UplinkTestCase {

	/***
	 * @var Token_Manager
	 */
	private $token_manager;

	/**
	 * The sample plugin slug
	 *
	 * @var string
	 */
	private $slug = 'sample';

	/**
	 * @var Resource
	 */
	private $plugin;

	protected function setUp(): void {
		parent::setUp();

		Config::set_token_auth_prefix( 'kadence_' );

		// Run init again to reload the Token/Provider.
		Uplink::init();

		// Main test domain is wordpress.test, create a completely custom domain.
		$sub_site_id = wpmu_create_blog( 'sub1.wordpress.test', '/', 'Test Subsite', 1 );

		$this->assertNotInstanceOf( WP_Error::class, $sub_site_id );
		$this->assertGreaterThan( 1, $sub_site_id );

		// Register the sample plugin as a developer would in their plugin.
		$this->plugin = Register::plugin(
			$this->slug,
			'Lib Sample',
			'1.0.10',
			'uplink/index.php',
			Sample_Plugin::class
		);

		switch_to_blog( $sub_site_id );

		$this->token_manager = $this->container->get( Token_Manager::class );
	}

	/**
	 * @env multisite
	 */
	public function test_it_stores_and_retrieves_a_token_from_the_network(): void {
		$this->assertTrue( is_multisite() );

		$this->assertNull( $this->token_manager->get( $this->plugin ) );

		$token = 'cd4b77be-985f-4737-89b7-eaa13b335fe8';

		$this->assertTrue( $this->token_manager->store( $token, $this->plugin ) );

		$this->assertSame( $token, $this->token_manager->get( $this->plugin ) );

		$this->assertSame( $token, get_network_option( get_current_network_id(), $this->token_manager->option_name() )[ $this->slug ] );
		$this->assertEmpty( get_option( $this->token_manager->option_name() ) );
	}

	/**
	 * @env multisite
	 */
	public function test_it_deletes_a_token(): void {
		$this->assertNull( $this->token_manager->get( $this->plugin ) );

		$token = 'b5aad022-71ca-4b29-85c2-70da5c8a5779';

		$this->assertTrue( $this->token_manager->store( $token, $this->plugin ) );

		$this->assertSame( $token, $this->token_manager->get( $this->plugin ) );

		$this->assertTrue( $this->token_manager->delete( $this->slug ) );

		$this->assertNull( $this->token_manager->get( $this->plugin ) );
	}

	/**
	 * @env multisite
	 */
	public function test_it_does_not_store_an_empty_token(): void {
		$this->assertNull( $this->token_manager->get( $this->plugin ) );

		$this->assertFalse( $this->token_manager->store( '', $this->plugin ) );

		$this->assertNull( $this->token_manager->get( $this->plugin ) );
	}

	/**
	 * @env multisite
	 */
	public function test_it_fetches_and_deletes_a_legacy_token(): void {
		$this->assertNull( $this->token_manager->get( $this->plugin ) );

		$token = 'b5aad022-71ca-4b29-85c2-70da5c8a5779';

		// Manually store a legacy string token.
		$this->assertTrue( update_network_option( get_current_network_id(), $this->token_manager->option_name(), $token ) );

		$this->assertTrue( $this->token_manager->store( $token, $this->plugin ) );

		$this->assertSame( $token, $this->token_manager->get( $this->plugin ) );

		$this->assertTrue( $this->token_manager->delete( $this->slug ) );

		$this->assertNull( $this->token_manager->get( $this->plugin ) );
	}

}
