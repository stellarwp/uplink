<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Features;

use StellarWP\Uplink\Features\Feature_Repository;
use StellarWP\Uplink\Features\Manager;
use StellarWP\Uplink\Features\Strategy\Resolver;
use StellarWP\Uplink\Features\Strategy\Theme_Strategy;
use StellarWP\Uplink\Features\Types\Theme;
use StellarWP\Uplink\Tests\UplinkTestCase;

final class ProviderTest extends UplinkTestCase {

	/**
	 * Tests that the Feature_Repository is registered as a singleton in the container.
	 *
	 * @return void
	 */
	public function test_it_registers_repository(): void {
		$this->assertInstanceOf( Feature_Repository::class, $this->container->get( Feature_Repository::class ) );
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
	 * Tests that the Resolver creates a Theme_Strategy for theme features.
	 *
	 * @return void
	 */
	public function test_it_registers_theme_strategy(): void {
		$resolver = $this->container->get( Resolver::class );
		$feature  = Theme::from_array(
			[
				'slug' => 'test-theme',
				'type' => 'theme',
				'name' => 'Test Theme',
			]
		);

		$this->assertInstanceOf( Theme_Strategy::class, $resolver->resolve( $feature ) );
	}
}
