<?php declare( strict_types=1 );

namespace StellarWP\Uplink\CLI;

use StellarWP\ContainerContract\ContainerInterface;
use StellarWP\Uplink\Catalog\Catalog_Repository;
use StellarWP\Uplink\CLI\Commands\Catalog;
use StellarWP\Uplink\CLI\Commands\Feature;
use StellarWP\Uplink\CLI\Commands\License;
use StellarWP\Uplink\Contracts\Abstract_Provider;
use StellarWP\Uplink\Features\Manager;
use StellarWP\Uplink\Legacy\License_Repository as Legacy_License_Repository;
use StellarWP\Uplink\Licensing\License_Manager;
use StellarWP\Uplink\Site\Data;
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
		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) { // @phpstan-ignore booleanNot.alwaysFalse, booleanOr.alwaysFalse (WP_CLI is only defined in CLI context)
			return;
		}

		$this->container->singleton(
			Feature::class,
			static function ( ContainerInterface $c ) {
				return new Feature( $c->get( Manager::class ) );
			}
		);

		$this->container->singleton(
			License::class,
			static function ( ContainerInterface $c ) {
				return new License(
					$c->get( License_Manager::class ),
					$c->get( Data::class ),
					$c->get( Legacy_License_Repository::class )
				);
			}
		);

		$this->container->singleton(
			Catalog::class,
			static function ( ContainerInterface $c ) {
				return new Catalog( $c->get( Catalog_Repository::class ) );
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
		WP_CLI::add_command( 'uplink license', $this->container->get( License::class ) );
		WP_CLI::add_command( 'uplink catalog', $this->container->get( Catalog::class ) );
	}
}
