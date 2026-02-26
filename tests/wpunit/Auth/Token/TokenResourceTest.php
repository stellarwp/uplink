<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Auth\Token;

use StellarWP\Uplink\Auth\Token\Contracts\Token_Manager;
use StellarWP\Uplink\Config;
use StellarWP\Uplink\Register;
use StellarWP\Uplink\Resources\Resource;
use StellarWP\Uplink\Tests\Sample_Plugin;
use StellarWP\Uplink\Tests\UplinkTestCase;
use StellarWP\Uplink\Uplink;

final class TokenResourceTest extends UplinkTestCase {

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

		// Register the sample plugin as a developer would in their plugin.
		$this->plugin = Register::plugin(
			$this->slug,
			'Lib Sample',
			'1.0.10',
			'uplink/index.php',
			Sample_Plugin::class
		);

		$this->token_manager = $this->container->get( Token_Manager::class );
	}

	public function test_it_gets_and_sets_a_token_from_a_resource(): void {
		$this->assertNull( $this->plugin->get_token() );

		$token = 'b0679a2e-b36d-41ca-8121-f43267751938';

		$this->assertTrue( $this->plugin->store_token( $token ) );
		$this->assertSame( $token, $this->plugin->get_token() );
		$this->assertSame( $token, $this->token_manager->get( $this->plugin ) );
	}
}
