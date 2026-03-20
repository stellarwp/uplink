<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\CLI;

use StellarWP\Uplink\CLI\Commands\Catalog;
use StellarWP\Uplink\CLI\Commands\Feature;
use StellarWP\Uplink\CLI\Commands\License;
use StellarWP\Uplink\CLI\Provider;
use StellarWP\Uplink\Tests\UplinkTestCase;
use StellarWP\Uplink\Tests\Traits\With_Uopz;

/**
 * Tests for the CLI Provider.
 *
 * @since 3.0.0
 */
final class ProviderTest extends UplinkTestCase {

	use With_Uopz;

	/**
	 * Tests that register() early-returns when WP_CLI is false.
	 *
	 * The provider should not register any command singletons
	 * when WP_CLI is falsy.
	 */
	public function test_register_skips_when_wp_cli_is_false(): void {
		$this->set_const_value( 'WP_CLI', false );

		$provider = new Provider( $this->container );
		$provider->register();

		$this->assertFalse( $this->container->isBound( Feature::class ) );
		$this->assertFalse( $this->container->isBound( License::class ) );
		$this->assertFalse( $this->container->isBound( Catalog::class ) );
	}

	/**
	 * Tests that register() binds all commands when WP_CLI is defined and truthy.
	 */
	public function test_register_binds_feature_command_when_wp_cli_is_defined(): void {
		$this->set_const_value( 'WP_CLI', true );

		$provider = new Provider( $this->container );
		$provider->register();

		$this->assertTrue( $this->container->isBound( Feature::class ) );
	}

	/**
	 * Tests that register() binds the License command when WP_CLI is defined and truthy.
	 */
	public function test_register_binds_license_command_when_wp_cli_is_defined(): void {
		$this->set_const_value( 'WP_CLI', true );

		$provider = new Provider( $this->container );
		$provider->register();

		$this->assertTrue( $this->container->isBound( License::class ) );
	}

	/**
	 * Tests that register() binds the Catalog command when WP_CLI is defined and truthy.
	 */
	public function test_register_binds_catalog_command_when_wp_cli_is_defined(): void {
		$this->set_const_value( 'WP_CLI', true );

		$provider = new Provider( $this->container );
		$provider->register();

		$this->assertTrue( $this->container->isBound( Catalog::class ) );
	}
}
