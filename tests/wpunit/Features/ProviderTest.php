<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Features;

use StellarWP\Uplink\Features\API\Client;
use StellarWP\Uplink\Features\Manager;
use StellarWP\Uplink\Features\Provider;
use StellarWP\Uplink\Features\REST\Feature_Controller;
use StellarWP\Uplink\Features\Strategy\Resolver;
use StellarWP\Uplink\Tests\UplinkTestCase;

final class ProviderTest extends UplinkTestCase {

	/**
	 * Tests that the API Client is registered as a singleton in the container.
	 *
	 * @return void
	 */
	public function test_it_registers_client(): void {
		$this->assertInstanceOf( Client::class, $this->container->get( Client::class ) );
	}

	/**
	 * Tests that the Strategy Resolver is registered as a singleton in the container.
	 *
	 * @return void
	 */
	public function test_it_registers_resolver(): void {
		$this->assertInstanceOf( Resolver::class, $this->container->get( Resolver::class ) );
	}

	/**
	 * Tests that the Feature Manager is registered as a singleton in the container.
	 *
	 * @return void
	 */
	public function test_it_registers_manager(): void {
		$this->assertInstanceOf( Manager::class, $this->container->get( Manager::class ) );
	}

	/**
	 * Tests that the Feature Controller is registered as a singleton in the container.
	 *
	 * @return void
	 */
	public function test_it_registers_feature_controller(): void {
		$this->assertInstanceOf( Feature_Controller::class, $this->container->get( Feature_Controller::class ) );
	}

	/**
	 * Tests that a callback is hooked to rest_api_init for route registration.
	 *
	 * @return void
	 */
	public function test_it_hooks_rest_api_init(): void {
		$this->assertGreaterThan(
			0,
			has_action( 'rest_api_init' ),
			'rest_api_init should have a registered callback.'
		);
	}

	/**
	 * @test
	 */
	public function it_should_register_routes_when_it_has_the_highest_version(): void {
		$called = 0;

		$mock = $this->makeEmpty(
			Feature_Controller::class,
			[
				'register_routes' => static function () use ( &$called ) {
					$called++;
				},
			] 
		);

		$this->container->singleton( Feature_Controller::class, $mock );

		$provider = $this->container->get( Provider::class );
		$provider->register_rest_routes();

		$this->assertSame( 1, $called, 'register_routes() should be called when this instance has the highest version.' );
	}

	/**
	 * @test
	 */
	public function it_should_not_register_routes_when_a_higher_version_exists(): void {
		$called = false;

		$mock = $this->makeEmpty(
			Feature_Controller::class,
			[
				'register_routes' => static function () use ( &$called ) {
					$called = true;
				},
			] 
		);

		$this->container->singleton( Feature_Controller::class, $mock );

		add_filter(
			'stellarwp/uplink/highest_version',
			static function () {
				return '99.0.0';
			}
		);

		$provider = $this->container->get( Provider::class );
		$provider->register_rest_routes();

		$this->assertFalse( $called, 'register_routes() should not be called when a higher version exists.' );
	}

	/**
	 * @test
	 */
	public function it_should_not_register_routes_when_already_claimed(): void {
		$called = false;

		$mock = $this->makeEmpty(
			Feature_Controller::class,
			[
				'register_routes' => static function () use ( &$called ) {
					$called = true;
				},
			] 
		);

		$this->container->singleton( Feature_Controller::class, $mock );

		do_action( 'stellarwp/uplink/handled/features_rest_routes' );

		$provider = $this->container->get( Provider::class );
		$provider->register_rest_routes();

		$this->assertFalse( $called, 'register_routes() should not be called when the action is already claimed.' );
	}

	/**
	 * @test
	 */
	public function it_should_only_register_routes_once(): void {
		$called = 0;

		$mock = $this->makeEmpty(
			Feature_Controller::class,
			[
				'register_routes' => static function () use ( &$called ) {
					$called++;
				},
			] 
		);

		$this->container->singleton( Feature_Controller::class, $mock );

		$provider = $this->container->get( Provider::class );
		$provider->register_rest_routes();
		$provider->register_rest_routes();

		$this->assertSame( 1, $called, 'register_routes() should only be called once even when invoked multiple times.' );
	}
}
