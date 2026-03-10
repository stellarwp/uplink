<?php declare( strict_types=1 );

namespace StellarWP\Uplink\CLI;

use StellarWP\ContainerContract\ContainerInterface;
use StellarWP\Uplink\CLI\Commands\Feature;
use StellarWP\Uplink\Contracts\Abstract_Provider;
use StellarWP\Uplink\Features\Manager;
use StellarWP\Uplink\Utils\Version;
use WP_CLI;

/**
 * Registers WP-CLI commands for the Uplink library.
 *
 * Early-returns when WP-CLI is not present, so command classes are never
 * instantiated during normal web requests.
 *
 * @since 3.0.0
 */
final class Provider extends Abstract_Provider {

	/**
	 * @inheritDoc
	 */
	public function register(): void {
		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) { // @phpstan-ignore booleanNot.alwaysFalse, booleanOr.alwaysFalse -- WP_CLI is only defined in CLI context; PHPStan always sees it as true.
			return;
		}

		$this->container->singleton(
			Feature::class,
			static function ( ContainerInterface $c ) {
				return new Feature( $c->get( Manager::class ) );
			}
		);

		WP_CLI::add_hook( 'after_wp_load', [ $this, 'register_commands' ] );
	}

	/**
	 * Registers all WP-CLI commands.
	 *
	 * Uses Version::should_handle() to prevent duplicate registration
	 * across vendor-prefixed copies of Uplink.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function register_commands(): void {
		if ( ! Version::should_handle( 'cli_commands' ) ) {
			return;
		}

		WP_CLI::add_command( 'uplink feature', $this->container->get( Feature::class ) );
	}
}
