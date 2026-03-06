<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Features\Strategy;

use InvalidArgumentException;
use StellarWP\Uplink\Features\Strategy\Flag_Strategy;
use StellarWP\Uplink\Features\Strategy\Plugin_Strategy;
use StellarWP\Uplink\Features\Strategy\Strategy_Factory;
use StellarWP\Uplink\Features\Strategy\Theme_Strategy;
use StellarWP\Uplink\Features\Types\Flag;
use StellarWP\Uplink\Features\Types\Plugin;
use StellarWP\Uplink\Features\Types\Theme;
use StellarWP\Uplink\Tests\UplinkTestCase;

final class Strategy_FactoryTest extends UplinkTestCase {

	/**
	 * The strategy factory under test.
	 *
	 * @var Strategy_Factory
	 */
	private Strategy_Factory $factory;

	/**
	 * Sets up the strategy factory before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->factory = new Strategy_Factory();
	}

	/**
	 * Tests that the factory creates a Plugin_Strategy for plugin features.
	 *
	 * @return void
	 */
	public function test_it_creates_plugin_strategy(): void {
		$feature = Plugin::from_array( [
			'slug'        => 'test-plugin',
			'type'        => 'plugin',
			'name'        => 'Test Plugin',
			'plugin_file' => 'test-plugin/test-plugin.php',
		] );

		$this->assertInstanceOf( Plugin_Strategy::class, $this->factory->make( $feature ) );
	}

	/**
	 * Tests that the factory creates a Flag_Strategy for flag features.
	 *
	 * @return void
	 */
	public function test_it_creates_flag_strategy(): void {
		$feature = Flag::from_array( [
			'slug' => 'test-flag',
			'type' => 'flag',
			'name' => 'Test Flag',
		] );

		$this->assertInstanceOf( Flag_Strategy::class, $this->factory->make( $feature ) );
	}

	/**
	 * Tests that the factory creates a Theme_Strategy for theme features.
	 *
	 * @return void
	 */
	public function test_it_creates_theme_strategy(): void {
		$feature = Theme::from_array( [
			'slug' => 'test-theme',
			'type' => 'theme',
			'name' => 'Test Theme',
		] );

		$this->assertInstanceOf( Theme_Strategy::class, $this->factory->make( $feature ) );
	}

	/**
	 * Tests that an exception is thrown for an unknown feature type.
	 *
	 * @return void
	 */
	public function test_it_throws_for_unknown_type(): void {
		$feature = $this->makeEmpty( \StellarWP\Uplink\Features\Types\Feature::class, [ 'get_type' => 'unknown' ] );

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'No strategy for feature type "unknown".' );

		$this->factory->make( $feature );
	}
}
